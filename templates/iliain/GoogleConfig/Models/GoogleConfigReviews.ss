<div class="form-group field" style="margin-top: 1.5rem;">
    <label for="review-badge" class="form__field-label">Badge</label>
    <div id="review-badge" class="reviews-inner">

        <div class="info-left">
            <img src="{$Image}" alt="{$Title}" width="100" height="100" title="{$Title}">
        </div>

        <div class="info-right">
            <div class="info-place">
                <a href="{$Link}" target="_blank" rel="nofollow noopener">
                    <span>{$Title}</span>
                </a>
            </div>
            <div>
                <span class="rating-text"><b>{$Rating}</b></span>
                <span class="google-stars">
                    <% loop $Stars %>
                        <div class="g-icon rating-star rating-<% if Full %>full<% else %>half<% end_if %>-star"></div>
                    <% end_loop %>
                </span>
            </div>

            <div class="based-on">Based on {$Total} review<% if $Total != 1 %>s<% end_if %></div>

            <div class="review-link">
                <a href="https://search.google.com/local/writereview?placeid={$PlaceID}">
                    Review Us
                </a>
            </div>
        </div>

    </div>
</div>

<% require css("iliain/silverstripe-google-config: client/css/config.css") %>
