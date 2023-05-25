<div class="form-group field" style="margin-top: 1.5rem;">
    <label for="review-badge" class="form__field-label">Badge</label>
    <div class="form__field-holder">
        <% include Iliain/GoogleConfig/Models/ReviewBadge %>
    </div>
</div>

<div class="form-group field" style="margin-top: 1.5rem;">
    <label for="review-list" class="form__field-label">Reviews</label>

        <% if $Reviews %>
            <div class="review-list">
                <% loop $Reviews %>
                    <% include Iliain/GoogleConfig/Models/ReviewsList %>
                <% end_loop %>
            </div>
        <% end_if %>

</div>
