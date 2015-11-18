<?php $this->layout('layout', ['title' => 'Enter Device Code']); ?>

<p>Enter the code shown on your device to continue.</p>

<form action="/auth/verify_code" method="get">
  <input type="text" name="code" placeholder="xxxxxx">
  <input type="submit">
</form>
