<?php

$ctx->pdf->properties['Title'] = iconv("UTF-8", "ISO-8859-15",
				       $metas->get('DC.Title.alternative'));

$lyt = new PL_Layout($ctx, "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec venenatis rutrum libero. Duis gravida, lorem id congue elementum, quam odio mollis nisi, at facilisis felis arcu non ligula. Integer mollis accumsan turpis. Etiam pellentesque viverra odio. Nunc auctor justo a dolor. Etiam auctor sollicitudin pede. Praesent pede. Aliquam libero sapien, pretium at, facilisis non, pellentesque eu, est. Morbi a tortor nec augue viverra mattis. In pede. Nam sed pede. Aenean pretium. Etiam pretium pretium elit. Aliquam ut risus. ");
$lyt->draw();

return;

$w = $page->getWidth();
$h = $page->getHeight();
$_x = 0;
$_y = $h;
$_y-= ($font->getLineHeight()/$font->getUnitsPerEm()) * 16*2;
$title = $metas->get('DC.Title');
//$title = $font->encodeString($title, 'UTF-8');
$title = wtk_strtoarray($title);
$numbers = $font->glyphNumbersForCharacters($title);
$widths = $font->widthsForGlyphs($numbers);
$width = array_sum($widths);
$width = $width/$font->getUnitsPerEm()*16;
$_x = ($w-$width)/2;
$page->drawText($metas->get('DC.Title'), $_x, $_y, "UTF-8");
