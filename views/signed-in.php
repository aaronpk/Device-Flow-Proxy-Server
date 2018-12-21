<?php $this->layout('layout', ['title' => $title]); ?>

<p>You successfully signed in! Now return to your device to finish.</p>

<script>
window.history.replaceState({}, false, '/auth/redirect');
</script>
