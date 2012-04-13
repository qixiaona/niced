header<br />
<?php if (isset($this->_zones['a']) && is_array($this->_zones['a'])):?>
<div><h2>Content grid A</h2>
<?php foreach ($this->_zones['a'] as $zone) :?>
	<?php $this->render($zone['name']);?>
<?php endforeach;?>
</div>
<?php endif;?>
<?php if (isset($this->_zones['b']) && is_array($this->_zones['b'])):?>
<div><h2>Content grid C</h2>
<?php foreach ($this->_zones['b'] as $zone) :?>
	<?php $this->render($zone['name']);?>
<?php endforeach;?>
</div>
<?php endif;?>
footer<br />