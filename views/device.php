<?php $this->layout('layout', ['title' => 'Enter Device Code']); ?>

<p>Enter the code shown on your device to continue.</p>

<form action="/auth/verify_code" method="get">
  <input type="text" name="code" placeholder="XXXX-XXXX" id="user_code" value="<?= $code ?>" autocomplete="off">
  <input type="submit">
</form>

<script>
document.getElementById("user_code").focus();
</script>