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

<?php foreach($alternatives as $alt): ?>
<?php extract($alt); ?>
<link rel="alternate"<?php
wtk_attr('type', $type);
wtk_attr('title', $title);
wtk_attr('href', 'http://'.$_SERVER['HTTP_HOST'].$href);
?> />
<?php endforeach; ?>

<?php $embeded = array(); ?>
<?php $et = ""; ?>
<?php foreach ($styles as $style): ?>
<?php $default = $default_style->id == $style->id; ?>
<?php $et = $default ? $style->title : $et; ?>
<?php $files = $style->getFiles($style_components, 'Xhtml'); ?>
<?php foreach ($files as $f): ?>
<?php extract($f); ?>
<?php
$ie = false;
if(preg_match("`((?:(?:lt|gt)e?_)?ie(?:_[\d\.]+)?)\.css$`i", $file, $match)) {
    $ie = explode('_', $match[1]);
}
else {
    $ie = null;
};
?>
<?php if ($default && $embed_style): ?>
<?php if (!isset($embeded[$medium])) { $embeded[$medium] = ''; } ?>
<?php $embeded[$medium].= !$ie && is_readable($file) ? file_get_contents($file) : ''; ?>
<?php else: ?>
<?php if ($ie): ?>
<!--[if <?php echo implode(' ', $ie); ?>]>
<?php endif; ?>
<link type="text/css" rel="<?php echo $default ? "" : "alternate "; ?>stylesheet" title="<?php echo $style->title; ?>" media="<?php echo $medium; ?>" href="<?php echo $file; ?>" />
<?php if ($ie): ?>
<![endif]-->
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
<?php endforeach; ?>

<?php foreach($embeded as $medium => $css): ?>
<style type="text/css" media="<?php echo $medium; ?>" title="<?php echo $et; ?>">
<!--/*--><![CDATA[<!--*/

  <?php echo str_replace('url("', 'url("'.dirname($files[0]['file']).'/', $css); ?>
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
  <body>
<?php if (isset($this->header)) $this->header->output(); ?>
<?php if (isset($this->content)) $this->content->output (); ?>
<?php if (isset($this->aside)) $this->aside->output(); ?>
<?php if (isset($this->footer)) $this->footer->output(); ?>
  </body>
</html>
