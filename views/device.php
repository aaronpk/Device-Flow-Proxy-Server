<?php $this->layout('layout', ['title' => 'Enter Device Code']); ?>

<form action="/auth/verify_code" method="get">
  <input type="text" name="code" placeholder="xxxxxx">
  <input type="submit">
</form>
