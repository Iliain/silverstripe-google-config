<% if $Reviews %>
    <div class="review-list">
        <% loop $Reviews %>
            <div class="review-badge-outer">
                <div class="review-box-upper">
            
                    <div class="info-left">
                        <img src="{$Photo}" alt="{$Author}" width="100" height="100" title="{$Author}" class="user-image">
                    </div>
            
                    <div class="info-right">
                        <a href="{$AuthorURL}" target="_blank" rel="nofollow noopener" class="author-link">
                            <span>{$Author}</span>
                        </a>
                    </div>
            
                </div>
            
                <div class="review-box-lower">
                    <div class="review-rating">
                        <span class="google-stars">
                            <% loop $Stars %>
                                <div class="rating-star rating-{$Value}-star"></div>
                            <% end_loop %>
                        </span>
            
                        <span class="rating-value">{$Time}</span>
                    </div>
                </div>
            
                <p class="review-text">{$Text.LimitCharacters(500,'...')}</p>
            </div>
        <% end_loop %>
    </div>
<% end_if %>

<% require css("iliain/silverstripe-google-config: client/css/review.css") %>
