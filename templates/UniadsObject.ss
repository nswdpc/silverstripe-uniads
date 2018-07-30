<% if $Zone %>
<div style="width:{$Zone.Width};height:{$Zone.Height};margin:0 auto;padding:0;overflow:hidden;">
<% end_if %>
<% if not $ExternalAd %>
    <a href="$Link"<% if $UseJsTracking %> data-adid="$ID"<% end_if %><% if $NewWindow %> target="_blank"<% end_if %>>$Content</a>
<% else %>
    $Content
<% end_if %>
<% if $Zone %>
</div>
<% end_if %>
