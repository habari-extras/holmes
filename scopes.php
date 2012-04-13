<?php $theme->display('header');?>
 
<div class="container">
	<h2><?php echo _t( 'Scope Management' ); ?></h2>
	<?php foreach( $active_scopes as $scope_slug => $scope ): ?>
	<a href="<?php echo URL::get( 'admin', array( 'page' => 'scope', 'scope' => $scope_slug ) ); ?>"><?php echo $scope->name; ?></a>
	<?php endforeach; ?>
</div>

<div class="container">
	<h2><?php echo _t( 'Add Scope' ); ?></h2>
	<?php $add_form->out(); ?>
</div>
 
<?php $theme->display('footer'); ?>