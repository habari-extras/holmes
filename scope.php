<?php $theme->display('header');?>
 
<div class="container navigation">
	<a href="<?php URL::out( 'admin', array( 'page' => 'scopes' ) ); ?>"><?php echo _t( 'All Scopes' ); ?></a>
</div>

<div class="container">
	<h2><?php echo _t( 'Scope Settings' ); ?></h2>
	<?php $config_form->out(); ?>
</div>

<div class="container controls transparent">
	<span class="pct50">
		<a class="delete button" href="<?php URL::out( 'admin', array( 'page' => 'scope', 'scope' => $scope->slug, 'action' => 'delete' ) ); ?>">Delete</a>
	</span>

</div>
 
<?php $theme->display('footer'); ?>