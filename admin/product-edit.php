<?php
/** Admin: product create/edit — details, images, variants, specs, features, videos, FAQs, categories, related, SEO. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$p) { admin_flash('Product not found.', 'err'); redirect(site_url('admin/products.php')); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? 'save';

    /* ---- image management actions (require existing product) ---- */
    if ($p && in_array($action, ['upload_image', 'delete_image', 'feature_image', 'reorder_images'], true)) {
        try {
            if ($action === 'upload_image') {
                $file = admin_upload_image('image', 'products');
                if ($file) {
                    q('INSERT INTO product_images (product_id, file, alt, sort_order) VALUES (?,?,?,?)',
                        [$id, $file, trim((string)($_POST['alt'] ?? $p['name'])), (int) val('SELECT COALESCE(MAX(sort_order),0)+1 FROM product_images WHERE product_id = ?', [$id])]);
                    admin_flash('Image uploaded.');
                }
            } elseif ($action === 'delete_image') {
                $img = row('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int)$_POST['image_id'], $id]);
                if ($img) { admin_delete_upload($img['file'], 'products'); q('DELETE FROM product_images WHERE id = ?', [$img['id']]); admin_flash('Image deleted.'); }
            } elseif ($action === 'feature_image') {
                q('UPDATE product_images SET is_featured = 0 WHERE product_id = ?', [$id]);
                q('UPDATE product_images SET is_featured = 1 WHERE id = ? AND product_id = ?', [(int)$_POST['image_id'], $id]);
                admin_flash('Featured image updated.');
            } elseif ($action === 'reorder_images') {
                foreach (($_POST['order'] ?? []) as $pos => $imgId) {
                    q('UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?', [$pos, (int)$imgId, $id]);
                }
                admin_flash('Image order saved.');
            }
            log_activity('product_images', "$action on product #$id");
        } catch (RuntimeException $ex) {
            admin_flash($ex->getMessage(), 'err');
        }
        redirect(site_url('admin/product-edit.php?id=' . $id . '#images'));
    }

    /* ---- main save ---- */
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? '')) ?: slugify($name);
    $data = [
        'sku'               => trim((string)($_POST['sku'] ?? '')),
        'slug'              => $slug,
        'name'              => $name,
        'tagline'           => trim((string)($_POST['tagline'] ?? '')),
        'short_description' => trim((string)($_POST['short_description'] ?? '')),
        'long_description'  => (string)($_POST['long_description'] ?? ''),
        'price'             => ($_POST['price'] ?? '') !== '' ? (float)$_POST['price'] : null,
        'compare_price'     => ($_POST['compare_price'] ?? '') !== '' ? (float)$_POST['compare_price'] : null,
        'show_price'        => isset($_POST['show_price']) ? 1 : 0,
        'wheel_config'      => in_array($_POST['wheel_config'] ?? '', ['3-wheel','4-wheel','enclosed','walker','part'], true) ? $_POST['wheel_config'] : '4-wheel',
        'stock_status'      => in_array($_POST['stock_status'] ?? '', array_keys(STOCK_BADGES), true) ? $_POST['stock_status'] : 'in_stock',
        'availability_text' => trim((string)($_POST['availability_text'] ?? '')),
        'badge_override'    => trim((string)($_POST['badge_override'] ?? '')) ?: null,
        'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
        'is_best_seller'    => isset($_POST['is_best_seller']) ? 1 : 0,
        'is_new_arrival'    => isset($_POST['is_new_arrival']) ? 1 : 0,
        'is_published'      => isset($_POST['is_published']) ? 1 : 0,
        'sort_order'        => (int)($_POST['sort_order'] ?? 0),
        'range_km'          => ($_POST['range_km'] ?? '') !== '' ? (int)$_POST['range_km'] : null,
        'top_speed_kmh'     => ($_POST['top_speed_kmh'] ?? '') !== '' ? (int)$_POST['top_speed_kmh'] : null,
        'capacity_kg'       => ($_POST['capacity_kg'] ?? '') !== '' ? (int)$_POST['capacity_kg'] : null,
        'keywords'          => trim((string)($_POST['keywords'] ?? '')),
        'seo_title'         => trim((string)($_POST['seo_title'] ?? '')),
        'seo_description'   => trim((string)($_POST['seo_description'] ?? '')),
    ];
    if ($name === '' || $data['sku'] === '') $errors[] = 'Name and SKU are required.';
    if ($data['price'] !== null && $data['price'] < 0) $errors[] = 'Price cannot be negative.';
    $dupe = val('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $id]);
    if ($dupe) $errors[] = 'That URL slug is already in use.';

    if (!$errors) {
        if ($p) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
            q("UPDATE products SET $set WHERE id = :__id", $data + ['__id' => $id]);
        } else {
            $cols = implode(',', array_keys($data));
            $marks = ':' . implode(',:', array_keys($data));
            q("INSERT INTO products ($cols) VALUES ($marks)", $data);
            $id = (int) db()->lastInsertId();
            $p = row('SELECT * FROM products WHERE id = ?', [$id]);
        }

        /* categories */
        q('DELETE FROM product_categories WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['categories'] ?? []) as $cid) {
            q('INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?,?)', [$id, (int)$cid]);
        }
        /* variants */
        q('DELETE FROM product_variants WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['variant_type'] ?? []) as $i => $vtype) {
            $vname = trim((string)($_POST['variant_name'][$i] ?? ''));
            if ($vname === '' || !in_array($vtype, ['colour','option'], true)) continue;
            q('INSERT INTO product_variants (product_id, type, name, hex, sku_suffix, price, is_default, sort_order) VALUES (?,?,?,?,?,?,?,?)',
                [$id, $vtype, $vname, trim((string)($_POST['variant_hex'][$i] ?? '')) ?: null,
                 trim((string)($_POST['variant_sku'][$i] ?? '')) ?: null,
                 ($_POST['variant_price'][$i] ?? '') !== '' ? (float)$_POST['variant_price'][$i] : null,
                 isset($_POST['variant_default'][$i]) ? 1 : 0, $i]);
        }
        /* specs */
        q('DELETE FROM product_specs WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['spec_name'] ?? []) as $i => $sname) {
            $sname = trim((string)$sname); $sval = trim((string)($_POST['spec_value'][$i] ?? ''));
            if ($sname === '' || $sval === '') continue;
            $group = trim((string)($_POST['spec_group'][$i] ?? 'Specifications')) ?: 'Specifications';
            q('INSERT INTO product_specs (product_id, spec_group, spec_name, spec_value, sort_order) VALUES (?,?,?,?,?)', [$id, $group, $sname, $sval, $i]);
        }
        /* features */
        q('DELETE FROM product_features WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['feature'] ?? []) as $i => $feat) {
            $feat = trim((string)$feat);
            if ($feat !== '') q('INSERT INTO product_features (product_id, feature, sort_order) VALUES (?,?,?)', [$id, $feat, $i]);
        }
        /* videos */
        q('DELETE FROM product_videos WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['video_url'] ?? []) as $i => $vurl) {
            $vurl = trim((string)$vurl);
            if ($vurl === '') continue;
            $provider = 'youtube'; $vid = '';
            if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,20})~', $vurl, $m)) { $vid = $m[1]; }
            elseif (preg_match('~vimeo\.com/(\d{6,12})~', $vurl, $m)) { $provider = 'vimeo'; $vid = $m[1]; }
            if ($vid === '') continue;
            q('INSERT INTO product_videos (product_id, provider, video_url, video_id, title, sort_order) VALUES (?,?,?,?,?,?)',
                [$id, $provider, $vurl, $vid, trim((string)($_POST['video_title'][$i] ?? '')) ?: null, $i]);
        }
        /* product FAQs */
        q('DELETE FROM product_faqs WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['faq_q'] ?? []) as $i => $fq) {
            $fq = trim((string)$fq); $fa = trim((string)($_POST['faq_a'][$i] ?? ''));
            if ($fq !== '' && $fa !== '') q('INSERT INTO product_faqs (product_id, question, answer, sort_order) VALUES (?,?,?,?)', [$id, $fq, $fa, $i]);
        }
        /* related */
        q('DELETE FROM product_related WHERE product_id = ?', [$id]);
        foreach ((array)($_POST['related'] ?? []) as $rid) {
            $rid = (int)$rid;
            if ($rid && $rid !== $id) q('INSERT IGNORE INTO product_related (product_id, related_product_id) VALUES (?,?)', [$id, $rid]);
        }

        log_activity('product_save', ($id ? 'Saved' : 'Created') . ' product ' . $name);
        admin_flash('Product saved.');
        redirect(site_url('admin/product-edit.php?id=' . $id));
    }
    $p = array_merge($p ?? [], $data); // repopulate on error
}

$allCats = rows('SELECT * FROM categories ORDER BY sort_order, name');
$allProducts = rows('SELECT id, name FROM products WHERE id <> ? ORDER BY name', [$id]);
$selCats = $id ? array_column(rows('SELECT category_id FROM product_categories WHERE product_id = ?', [$id]), 'category_id') : [];
$selRelated = $id ? array_column(rows('SELECT related_product_id FROM product_related WHERE product_id = ?', [$id]), 'related_product_id') : [];
$images = $id ? product_images($id) : [];
$variants = $id ? product_variants($id) : [];
$specs = $id ? rows('SELECT * FROM product_specs WHERE product_id = ? ORDER BY spec_group, sort_order', [$id]) : [];
$features = $id ? product_features($id) : [];
$videos = $id ? product_videos($id) : [];
$pfaqs = $id ? product_faqs($id) : [];
$v = fn(string $k, $default = '') => e((string)($p[$k] ?? $default));

admin_head($p ? 'Edit: ' . $p['name'] : 'Add product', 'products.php');
?>
<?php foreach ($errors as $err): ?><div class="flash flash-err"><?= e($err) ?></div><?php endforeach; ?>

<?php if ($p): ?>
<section class="panel" id="images">
    <h2>Images</h2>
    <div class="img-grid">
        <?php foreach ($images as $img): ?>
        <div class="img-card <?= $img['is_featured'] ? 'featured' : '' ?>" data-img-id="<?= (int)$img['id'] ?>">
            <img src="<?= e(img_thumb_url($img['file'])) ?>" alt="<?= e($img['alt'] ?? '') ?>">
            <div class="img-actions">
                <?php if ($img['is_featured']): ?><span class="chip">Featured</span>
                <?php else: ?>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="feature_image"><input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>"><button class="btn btn-sm btn-ghost">Set featured</button></form>
                <?php endif; ?>
                <form method="post" class="inline" data-confirm="Delete this image?"><?= csrf_field() ?><input type="hidden" name="action" value="delete_image"><input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </div>
            <span class="drag-hint">⋮⋮ drag to reorder</span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($images): ?>
    <form method="post" id="reorderForm"><?= csrf_field() ?><input type="hidden" name="action" value="reorder_images">
        <div id="orderInputs"></div>
        <button class="btn btn-sm">Save order</button>
    </form>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="upload-row">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload_image">
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <input type="text" name="alt" placeholder="Alt text (accessibility + SEO)" maxlength="190">
        <button class="btn">Upload</button>
    </form>
</section>
<?php else: ?>
<div class="flash flash-warn">Save the product first, then you can upload images.</div>
<?php endif; ?>

<form method="post" class="admin-form">
<?= csrf_field() ?>
<section class="panel">
    <h2>Core details</h2>
    <div class="form-grid">
        <label class="span2">Product name <input name="name" value="<?= $v('name') ?>" required maxlength="190"></label>
        <label>SKU <input name="sku" value="<?= $v('sku') ?>" required maxlength="60"></label>
        <label>URL slug <input name="slug" value="<?= $v('slug') ?>" maxlength="160" placeholder="auto-generated if blank"></label>
        <label class="span2">Tagline <input name="tagline" value="<?= $v('tagline') ?>" maxlength="255"></label>
        <label class="span2">Short description <textarea name="short_description" rows="2"><?= $v('short_description') ?></textarea></label>
        <label class="span2">Long description (HTML allowed) <textarea name="long_description" rows="8"><?= $v('long_description') ?></textarea></label>
    </div>
</section>

<section class="panel">
    <h2>Pricing &amp; availability</h2>
    <div class="form-grid">
        <label>Price (CAD) <input type="number" step="0.01" min="0" name="price" value="<?= $v('price') ?>"></label>
        <label>Compare-at price <input type="number" step="0.01" min="0" name="compare_price" value="<?= $v('compare_price') ?>"></label>
        <label>Type
            <select name="wheel_config">
                <?php foreach (['3-wheel','4-wheel','enclosed','walker','part'] as $wc): ?>
                <option value="<?= $wc ?>" <?= ($p['wheel_config'] ?? '') === $wc ? 'selected' : '' ?>><?= ucfirst($wc) ?></option>
                <?php endforeach; ?>
            </select></label>
        <label>Stock status
            <select name="stock_status">
                <?php foreach (STOCK_BADGES as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= ($p['stock_status'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                <?php endforeach; ?>
            </select></label>
        <label class="span2">Availability note <input name="availability_text" value="<?= $v('availability_text') ?>" maxlength="190" placeholder="e.g. Ships in 1–3 business days"></label>
        <label>Badge override <input name="badge_override" value="<?= $v('badge_override') ?>" maxlength="30" placeholder="e.g. LIMITED"></label>
        <label>Sort order <input type="number" name="sort_order" value="<?= $v('sort_order', '0') ?>"></label>
        <div class="check-row span2">
            <label class="check"><input type="checkbox" name="show_price" <?= ($p['show_price'] ?? 1) ? 'checked' : '' ?>> Show price on storefront</label>
            <label class="check"><input type="checkbox" name="is_published" <?= ($p['is_published'] ?? 1) ? 'checked' : '' ?>> Published</label>
            <label class="check"><input type="checkbox" name="is_best_seller" <?= ($p['is_best_seller'] ?? 0) ? 'checked' : '' ?>> Best seller</label>
            <label class="check"><input type="checkbox" name="is_new_arrival" <?= ($p['is_new_arrival'] ?? 0) ? 'checked' : '' ?>> New arrival</label>
            <label class="check"><input type="checkbox" name="is_featured" <?= ($p['is_featured'] ?? 0) ? 'checked' : '' ?>> Featured story</label>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Key specifications (cards + filters)</h2>
    <div class="form-grid">
        <label>Range (km) <input type="number" min="0" name="range_km" value="<?= $v('range_km') ?>"></label>
        <label>Top speed (km/h) <input type="number" min="0" name="top_speed_kmh" value="<?= $v('top_speed_kmh') ?>"></label>
        <label>Weight capacity (kg) <input type="number" min="0" name="capacity_kg" value="<?= $v('capacity_kg') ?>"></label>
        <label>Search keywords <input name="keywords" value="<?= $v('keywords') ?>" maxlength="255"></label>
    </div>
    <h3>Full spec sheet</h3>
    <div class="rep-block" id="specBlock">
        <?php foreach ($specs as $s): ?>
        <div class="rep-row">
            <input name="spec_group[]" value="<?= e($s['spec_group']) ?>" placeholder="Group (e.g. Performance)" maxlength="60">
            <input name="spec_name[]" value="<?= e($s['spec_name']) ?>" placeholder="Spec name" maxlength="120">
            <input name="spec_value[]" value="<?= e($s['spec_value']) ?>" placeholder="Value" maxlength="255">
            <button type="button" class="btn btn-sm btn-danger rep-del">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm" data-add="specBlock"
        data-row='<div class="rep-row"><input name="spec_group[]" placeholder="Group (e.g. Performance)" maxlength="60"><input name="spec_name[]" placeholder="Spec name" maxlength="120"><input name="spec_value[]" placeholder="Value" maxlength="255"><button type="button" class="btn btn-sm btn-danger rep-del">×</button></div>'>+ Add spec</button>

    <h3>Feature bullets</h3>
    <div class="rep-block" id="featBlock">
        <?php foreach ($features as $f): ?>
        <div class="rep-row"><input name="feature[]" value="<?= e($f['feature']) ?>" maxlength="255" class="grow"><button type="button" class="btn btn-sm btn-danger rep-del">×</button></div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm" data-add="featBlock"
        data-row='<div class="rep-row"><input name="feature[]" maxlength="255" class="grow" placeholder="Feature"><button type="button" class="btn btn-sm btn-danger rep-del">×</button></div>'>+ Add feature</button>
</section>

<section class="panel">
    <h2>Variants &amp; colours</h2>
    <div class="rep-block" id="varBlock">
        <?php foreach ($variants as $vr): ?>
        <div class="rep-row">
            <select name="variant_type[]">
                <option value="colour" <?= $vr['type'] === 'colour' ? 'selected' : '' ?>>Colour</option>
                <option value="option" <?= $vr['type'] === 'option' ? 'selected' : '' ?>>Option</option>
            </select>
            <input name="variant_name[]" value="<?= e($vr['name']) ?>" placeholder="Name" maxlength="90">
            <input name="variant_hex[]" value="<?= e($vr['hex'] ?? '') ?>" placeholder="#hex (colours)" maxlength="9" class="sm">
            <input name="variant_sku[]" value="<?= e($vr['sku_suffix'] ?? '') ?>" placeholder="SKU suffix" maxlength="30" class="sm">
            <input type="number" step="0.01" name="variant_price[]" value="<?= e((string)($vr['price'] ?? '')) ?>" placeholder="± price" class="sm">
            <label class="check"><input type="checkbox" name="variant_default[<?= (int)$vr['sort_order'] ?>]" <?= $vr['is_default'] ? 'checked' : '' ?>> Default</label>
            <button type="button" class="btn btn-sm btn-danger rep-del">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm" data-add="varBlock"
        data-row='<div class="rep-row"><select name="variant_type[]"><option value="colour">Colour</option><option value="option">Option</option></select><input name="variant_name[]" placeholder="Name" maxlength="90"><input name="variant_hex[]" placeholder="#hex" maxlength="9" class="sm"><input name="variant_sku[]" placeholder="SKU suffix" maxlength="30" class="sm"><input type="number" step="0.01" name="variant_price[]" placeholder="± price" class="sm"><button type="button" class="btn btn-sm btn-danger rep-del">×</button></div>'>+ Add variant</button>
</section>

<section class="panel">
    <h2>Videos (YouTube / Vimeo)</h2>
    <div class="rep-block" id="vidBlock">
        <?php foreach ($videos as $vd): ?>
        <div class="rep-row">
            <input name="video_url[]" value="<?= e($vd['video_url']) ?>" placeholder="https://youtube.com/watch?v=…" maxlength="255" class="grow">
            <input name="video_title[]" value="<?= e($vd['title'] ?? '') ?>" placeholder="Title (optional)" maxlength="190">
            <button type="button" class="btn btn-sm btn-danger rep-del">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm" data-add="vidBlock"
        data-row='<div class="rep-row"><input name="video_url[]" placeholder="https://youtube.com/watch?v=…" maxlength="255" class="grow"><input name="video_title[]" placeholder="Title (optional)" maxlength="190"><button type="button" class="btn btn-sm btn-danger rep-del">×</button></div>'>+ Add video</button>
</section>

<section class="panel">
    <h2>Product FAQs</h2>
    <div class="rep-block" id="faqBlock">
        <?php foreach ($pfaqs as $fq): ?>
        <div class="rep-row wrap">
            <input name="faq_q[]" value="<?= e($fq['question']) ?>" placeholder="Question" maxlength="255" class="grow">
            <textarea name="faq_a[]" rows="2" placeholder="Answer"><?= e($fq['answer']) ?></textarea>
            <button type="button" class="btn btn-sm btn-danger rep-del">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm" data-add="faqBlock"
        data-row='<div class="rep-row wrap"><input name="faq_q[]" placeholder="Question" maxlength="255" class="grow"><textarea name="faq_a[]" rows="2" placeholder="Answer"></textarea><button type="button" class="btn btn-sm btn-danger rep-del">×</button></div>'>+ Add FAQ</button>
</section>

<section class="panel">
    <h2>Categories &amp; related products</h2>
    <div class="form-grid">
        <div><strong>Categories</strong>
            <?php foreach ($allCats as $c): ?>
            <label class="check"><input type="checkbox" name="categories[]" value="<?= (int)$c['id'] ?>" <?= in_array($c['id'], $selCats) ? 'checked' : '' ?>> <?= e($c['name']) ?></label>
            <?php endforeach; ?>
        </div>
        <div><strong>Related products (max 4 shown)</strong>
            <?php foreach ($allProducts as $rp): ?>
            <label class="check"><input type="checkbox" name="related[]" value="<?= (int)$rp['id'] ?>" <?= in_array($rp['id'], $selRelated) ? 'checked' : '' ?>> <?= e($rp['name']) ?></label>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel">
    <h2>SEO</h2>
    <div class="form-grid">
        <label class="span2">SEO title <input name="seo_title" value="<?= $v('seo_title') ?>" maxlength="190"></label>
        <label class="span2">SEO description <textarea name="seo_description" rows="2" maxlength="320"><?= $v('seo_description') ?></textarea></label>
    </div>
</section>

<div class="save-bar">
    <button class="btn btn-primary btn-lg">Save product</button>
    <a class="btn btn-ghost" href="<?= e(site_url('admin/products.php')) ?>">Back to products</a>
</div>
</form>
<?php admin_foot(); ?>
