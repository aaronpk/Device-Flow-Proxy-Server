<?php $this->layout('layout', ['title' => 'Enter Device Code']); ?>

<p>Enter the code shown on your device to continue.</p>

<form action="/auth/verify_code" method="get">
  <input type="text" name="code" placeholder="xxxxxx" id="user_code">
  <input type="submit">
</form>

<script>
document.getElementById("user_code").focus()
</script>