<?php

$baseurl = '/';
$dojodbg = 'false';

?>
<!doctype html>
<html lang="fr">
<head>
<title><?php echo $metas->get('DC.Title.alternative'); ?></title>

<meta charset="utf-8" />
<?php foreach($metas as $name => $content): ?>
<?php if($content): ?>
<meta name="<?php echo $name; ?>" content="<?php echo htmlspecialchars($content); ?>" />
<?php endif; ?>
<?php endforeach; ?>

<base href="<?php echo $baseurl; ?>" />

<link rel="shortcut icon" type="image/png" href="<?php echo $baseurl.$default_style->getFavicon(); ?>" />
<?php if ($sitemap): ?>
<link rel="sitemap" type="application/xml" href="<?php echo $sitemap; ?>" />
<?php endif; ?>

<?php foreach($alternatives as $alt): ?>
<?php extract($alt); ?>
<link rel="alternate"<?php
wtk_attr('type', $type);
wtk_attr('title', $title);
wtk_attr('href', 'http://'.$_SERVER['HTTP_HOST'].$href);
?> />
<?php endforeach; ?>

<?php $style = $default_style ?>
<?php foreach($style_components as $style_component): ?>
<?php $files = $style->getFiles(array($style_component), 'Html5'); ?>
<?php switch($style_component): ?>
<?php default: ?>
<?php foreach ($files as $f): ?>
<?php extract($f); ?>
<?php if ($embed_style || $style_component === 'inline'): ?>
<?php $css = file_get_contents($file); ?>
<?php if ($embed_style): ?>
<?php $css = str_replace('url("', 'url("'.dirname($f['url']).'/', $css); ?>
<?php endif; ?>
<style type="text/css" media="all">
<!--/*--><![CDATA[<!--*/
<?php echo $css; ?>
/*]]>*/-->
</style>
<?php else: ?>
<link type="text/css" rel="stylesheet" media="all"<?php wtk_attr('href', $baseurl.$url); ?> />
<?php endif; ?>
<?php endforeach; ?>
<?php break; ?>
<?php endswitch; ?>
<?php endforeach; ?>
  </head>
<body<?php wtk_classes($flags); ?>>
<div id="body">
<?php if (isset($this->header)) $this->header->output(); ?>
<div id="contentwrapper"><?php if (isset($this->content)) $this->content->output (); ?></div>
<?php if (isset($this->aside)) $this->aside->output(); ?>
<?php if (isset($this->footer)) $this->footer->output(); ?>
<hr id="clearer" style="clear: both; visibility: hidden;" />
</div>

<?php if (count($dojoTypes)): ?>
<script language="javascript" type="text/javascript">
	 var djConfig = {isDebug:<?php echo $dojodbg; ?>,allowFirebugLite:false,parseOnLoad:true,locale:"fr"};
</script>
<script language="javascript" type="text/javascript" src="static/scripts/dojo/dojo.js"></script>
<script language="javascript" type="text/javascript">
<?php foreach($dojoTypes as $type): ?>
	 dojo.require("<?php echo $type;?>");
<?php endforeach;?>
dojo.require("dojo.parser");
</script>
<?php endif; ?>
  </body>
</html>
