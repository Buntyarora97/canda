<?php
/**
 * Global enquiry modal — opened by any [data-buy-now] or [data-enquire-general].
 * The selected product data is fetched server-side via /api/product-summary.php
 * (never trusted from the browser) and re-verified on submission.
 */
declare(strict_types=1);
?>
<div class="enquiry-modal" id="enquiryModal" hidden>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="enquiryTitle">
        <button type="button" class="modal-close" data-modal-close aria-label="Close enquiry form">&times;</button>

        <div class="modal-body" id="enquiryFormView">
            <div class="modal-product" id="enquiryProductCard" hidden>
                <img id="enquiryThumb" src="" alt="" width="88" height="88">
                <div>
                    <p class="modal-eyebrow">Interested in</p>
                    <h3 id="enquiryTitle">this product</h3>
                    <p class="modal-product-meta" id="enquiryMeta"></p>
                </div>
            </div>
            <div class="modal-product" id="enquiryGeneralCard" hidden>
                <div>
                    <p class="modal-eyebrow">Contact</p>
                    <h3 id="enquiryGeneralTitle">Ask the GIO team</h3>
                    <p class="modal-product-meta">Questions about any model, parts, or your order — we're here to help.</p>
                </div>
            </div>

            <form id="enquiryForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" id="fProductId" value="">
                <input type="hidden" name="variant" id="fVariant" value="">
                <input type="hidden" name="colour" id="fColour" value="">
                <input type="hidden" name="page_url" id="fPageUrl" value="">
                <input type="hidden" name="utm_source" id="fUtmSource" value="">
                <input type="hidden" name="utm_medium" id="fUtmMedium" value="">
                <input type="hidden" name="utm_campaign" id="fUtmCampaign" value="">
                <input type="hidden" name="referrer" id="fReferrer" value="">
                <!-- Honeypot: invisible to humans, bots fill it -->
                <div class="hp-field" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    <label>Company URL <input type="text" name="company_url" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="fFirstName">First Name <span class="req">*</span></label>
                        <input type="text" id="fFirstName" name="first_name" required maxlength="60" autocomplete="given-name">
                        <span class="field-error" data-error-for="first_name"></span>
                    </div>
                    <div class="field">
                        <label for="fLastName">Last Name</label>
                        <input type="text" id="fLastName" name="last_name" maxlength="60" autocomplete="family-name">
                    </div>
                    <div class="field">
                        <label for="fEmail">Email <span class="req">*</span></label>
                        <input type="email" id="fEmail" name="email" required maxlength="120" autocomplete="email">
                        <span class="field-error" data-error-for="email"></span>
                    </div>
                    <div class="field">
                        <label for="fPhone">Phone <span class="req">*</span></label>
                        <input type="tel" id="fPhone" name="phone" required maxlength="30" autocomplete="tel">
                        <span class="field-error" data-error-for="phone"></span>
                    </div>
                    <div class="field">
                        <label for="fProvince">Province <span class="req">*</span></label>
                        <select id="fProvince" name="province" required>
                            <option value="">Select province…</option>
                            <?php foreach (provinces() as $code => $name): ?>
                            <option value="<?= e($code) ?>"><?= e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error" data-error-for="province"></span>
                    </div>
                    <div class="field">
                        <label for="fCity">City <span class="req">*</span></label>
                        <input type="text" id="fCity" name="city" required maxlength="80" autocomplete="address-level2">
                        <span class="field-error" data-error-for="city"></span>
                    </div>
                    <div class="field">
                        <label for="fPostal">Postal Code</label>
                        <input type="text" id="fPostal" name="postal_code" maxlength="7" autocomplete="postal-code" placeholder="A1A 1A1">
                        <span class="field-error" data-error-for="postal_code"></span>
                    </div>
                    <div class="field">
                        <span class="field-label" id="prefLabel">Preferred Contact</span>
                        <div class="radio-row" role="radiogroup" aria-labelledby="prefLabel">
                            <label class="radio-pill"><input type="radio" name="preferred_contact" value="Phone"> Phone</label>
                            <label class="radio-pill"><input type="radio" name="preferred_contact" value="Email" checked> Email</label>
                        </div>
                    </div>
                    <div class="field field-full">
                        <label for="fMessage">Question / Message</label>
                        <textarea id="fMessage" name="message" rows="4" maxlength="2000" placeholder="Anything we should know? Delivery questions, accessories, timing…"></textarea>
                    </div>
                    <div class="field field-full">
                        <label class="check-row">
                            <input type="checkbox" name="consent" value="1" required>
                            <span>I agree to be contacted regarding this product enquiry.</span>
                        </label>
                        <span class="field-error" data-error-for="consent"></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" id="enquirySubmit">
                    <span class="btn-label">Send Enquiry</span>
                    <span class="btn-spinner" hidden></span>
                </button>
                <p class="form-note">No payment is taken online. The GIO team will contact you to complete your order.</p>
            </form>
        </div>

        <div class="modal-success" id="enquirySuccessView" hidden>
            <div class="success-check" aria-hidden="true">
                <svg viewBox="0 0 52 52" width="56" height="56"><circle cx="26" cy="26" r="24" fill="none"/><path fill="none" d="M14 27l8 8 16-17"/></svg>
            </div>
            <h3>Thanks — your GIO enquiry is on its way.</h3>
            <p>Our team can now follow up regarding the product you selected.</p>
            <div class="success-product" id="successProduct" hidden>
                <img id="successThumb" src="" alt="" width="72" height="72">
                <div>
                    <strong id="successName"></strong>
                    <span class="success-ref">Reference: <b id="successRef"></b></span>
                </div>
            </div>
            <button type="button" class="btn btn-outline" data-modal-close>Continue Browsing</button>
        </div>
    </div>
</div>
