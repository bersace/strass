<?php

$creation = $metas->creation;
$year = date('Y');

$snkp = isset($_SERVER['ORIG_SCRIPT_NAME']) ? 'ORIG_' : '';
$sn = $_SERVER[$snkp.'SCRIPT_NAME'];
$baseurl = '/';
$dojodbg = 'false';

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $metas->get('DC.Title.alternative'); ?></title>

<?php foreach($metas as $name => $content): ?>
<?php if($content): ?>
<meta name="<?php echo $name; ?>" content="<?php echo htmlspecialchars($content); ?>" />
<?php endif; ?>
<?php endforeach; ?>

<base href="<?php echo $baseurl; ?>" />

<link rel="shortcut icon" type="image/png" href="/<?php echo $default_style->getFavicon(); ?>" />
<?php if ($sitemap): ?>
<link rel="sitemap" type="application/xml" href="<?php echo $sitemap; ?>" />
<?php endif; ?>

</style>
<?php foreach($alternatives as $alt): ?>
<?php extract($alt); ?>
<link rel="alternate"<?php
wtk_attr('type', $type);
wtk_attr('title', $title);
wtk_attr('href', 'http://'.$_SERVER['HTTP_HOST'].$href);
?> />
<?php endforeach; ?>

<?php foreach ($default_style->getFiles(array('inline'), 'Xhtml') as $row): ?>
<?php extract($row); ?>
<style type="text/css">
<?php readfile($file); ?>
</style>
<?php endforeach; ?>

<?php $embeded = array(); ?>
<?php $et = ""; ?>
<?php foreach ($styles as $style): ?>
<?php $default = $default_style->id == $style->id; ?>
<?php $et = $default ? $style->title : $et; ?>
<?php $files = $style->getFiles($style_components, 'Xhtml'); ?>
<?php foreach ($files as $f): ?>
<?php extract($f); ?>
<?php if ($default && $embed_style): ?>
<?php
if (!isset($embeded[$medium])) {
  $embeded[$medium] = '';
}
?>
<?php $embeded[$medium].= is_readable($file) ? file_get_contents($file) : ''; ?>
<?php else: ?>
<link type="text/css" rel="<?php echo $default ? "" : "alternate "; ?>stylesheet" <?php
  wtk_attr('title', $style->title); wtk_attr('media', $medium); wtk_attr('href', $file); ?> />
<?php endif; ?>
<?php endforeach; ?>
<?php endforeach; ?>

  <?php $cssbaseurl = @dirname($files[0]['file']).'/'; ?>
<?php foreach($embeded as $medium => $css): ?>
<style type="text/css" media="<?php echo $medium; ?>" title="<?php echo $et; ?>">
<!--/*--><![CDATA[<!--*/

  <?php echo str_replace('url("', 'url("'.$cssbaseurl, $css); ?>
/*]]>*/-->
</style>
<?php endforeach; ?>

<?php if (count($dojoTypes)): ?>
<script language="javascript" type="text/javascript">
	 var djConfig = {isDebug:<?php echo $dojodbg; ?>,allowFirebugLite:false,parseOnLoad:true,locale:"fr"};
</script>
<script language="javascript" type="text/javascript" src="data/scripts/dojo/dojo.js"></script>
<script language="javascript" type="text/javascript">
<?php foreach($dojoTypes as $type): ?>
	 dojo.require("<?php echo $type;?>");
<?php endforeach;?>
dojo.require("dojo.parser");
</script>
<?php endif; ?>
  </head>
<body<?php wtk_classes($flags); ?>>
<div id="body">
<?php if (isset($this->header)) $this->header->output(); ?>
<div id="contentwrapper"><?php if (isset($this->content)) $this->content->output (); ?></div>
<?php if (isset($this->aside)) $this->aside->output(); ?>
<?php if (isset($this->footer)) $this->footer->output(); ?>
<hr id="clearer" style="clear: both; visibility: hidden;" />
</div>
  </body>
</html>
