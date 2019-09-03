

<script src="https://cdn.jsdelivr.net/npm/jdenticon@2.1.0" async>
</script>
<svg data-jdenticon-value="user127" width="80" height="80">
    Fallback text or image for browsers not supporting inline svg.
</svg>
<?php
include_once("vendor/autoload.php");
$icon = new \Jdenticon\Identicon();
$icon->setValue('Value to be hashed');
$icon->setSize(100);
$icon->displayImage();
//print_r($icon);
?>