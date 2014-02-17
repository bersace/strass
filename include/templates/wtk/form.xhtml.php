<?php
reset ($model->submissions);
$submission = current ($model->submissions);
if ($submission) {
  $action = $submission->handler;
  switch ($submission->method) {
  case Wtk_Form_Model_Submission::METHOD_POST:
    $method = 'post';
    $enctype = NULL;
    break;
  case Wtk_Form_Model_Submission::METHOD_GET:
    $method = 'get';
    $enctype = NULL;
    break;
  case Wtk_Form_Model_Submission::METHOD_FORM_DATA:
  case Wtk_Form_Model_Submission::METHOD_MULTIPART:
    $method = 'post';
    $enctype = 'multipart/form-data';
    break;
  default:
    $method = NULL;
    $enctype = NULL;
    break;
  }
}
else {
  $method = $enctype = $action = null;
}
?>
<form<?php
wtk_id_classes ($id, $flags, $dojoType);
wtk_attr ('method', $method);
wtk_attr ('enctype', $enctype);
wtk_attr ('action', $action);
?>>
<?php if($model->hasInstanceByType('Wtk_Form_Model_Instance_File')): ?>
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo wtk_steno_to_int(ini_get('upload_max_filesize')); ?>" />
<?php endif; ?>
<?php if (isset ($this->errors)): ?>
<?php $this->errors->output (); ?>
<?php endif; ?>
<?php $this->outputChildren(); ?>
</form>