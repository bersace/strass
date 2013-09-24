<?php


// polices
$ff = $document->fonts;
$dv = $ff->addFontFace("DejaVu Sans");
$dvs = $ff->addFontFace("DejaVu Serif", "Book");

// styles
$ss = $document->styles;
$s = $ss->addStyle_Default(Dio_Style::FAMILY_TABLE_CELL);
$p = $s->addPropertiesText();
$p->setFont($dv);
$p->setSize("10pt");

$std = $ss->addStyle("Standard", Dio_Style::FAMILY_TABLE_CELL, null);

$tb = $ss->addStyle("Text Body", Dio_Style::FAMILY_TABLE_CELL, $std);
$p = $tb->addPropertiesText();
$p->setFont($dvs);
$p->setSize("10pt");

$h = $ss->addStyle("Heading", Dio_Style::FAMILY_TABLE_CELL, $std, $tb);
$p = $s->addPropertiesText();
$p->setSize("14pt");
$p = $h->addPropertiesParagraph();
$p->setMarginTop("0.423cm");
$p->setMarginBottom("0.212cm");

$h1 = $ss->addStyle("Heading 1", Dio_Style::FAMILY_TABLE_CELL, $h, $tb);
$p = $h1->addPropertiesText();
$p->setSize("14pt");
$p->setWeight("bold");

$h2 = $ss->addStyle("Heading_2", Dio_Style::FAMILY_TABLE_CELL, $h);
$p = $h2->addPropertiesText();
$p->setSize("13pt");
$p->setWeight("bold");
$p->setFontStyle(Dio_Style_Properties_Text::FONT_STYLE_ITALIC);
