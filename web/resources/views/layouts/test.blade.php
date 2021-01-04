<meta name="csrf-token" content="{{ csrf_token() }}"/>
<div id="test">
</div>
<script type="text/javascript" language="javascript" src="/js/vconsole.min.js"></script>
<script>
  var vConsole = new VConsole();
</script>
<script src="{{ mix('js/test.js' . '?t=' . time()) }}"></script>
