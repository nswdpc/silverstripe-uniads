<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title><%t UniadsObject.PreviewTitle "Ad Preview:" %> $Title</title>
<% base_tag %>
<style type="text/css">
body {
    margin: 0;
    padding: 0;
    background: #000;
    color: #fff;
    font-family: sans-serif;
}
</style>
</head>
<body>
<% if $Zone %>
    <div style="width:$Zone.Width;height:$Zone.Height;margin:0 auto;padding:0;overflow:hidden;">
<% end_if %>
<% if not $ExternalAd %>
    <a href="$Link"<% if $UseJsTracking %> data-adid="$ID"<% end_if %><% if $NewWindow %> target="_blank"<% end_if %>>$Content</a>
<% else %>
    $Content
<% end_if %>
<% if $Zone %>
    </div>
    <p>Width: {$Zone.Width}, Height: {$Zone.Height}</p>
<% end_if %>
</body>
</html>
