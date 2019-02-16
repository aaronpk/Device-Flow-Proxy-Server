<?php $this->layout('layout', ['title' => 'Enter Device Code']); ?>

<?php if($code): ?>
<p>Confirm the code below matches the code shown on the device.</p>
<?php else: ?>
<p>Enter the code shown on your device to continue.</p>
<?php endif ?>

<form action="/auth/verify_code" method="get">
  <input type="text" name="code" placeholder="XXXX-XXXX" id="user_code" value="<?= $code ?>" autocomplete="off">
  <input type="submit">
</form>

<script>
document.getElementById("user_code").focus();
</script>