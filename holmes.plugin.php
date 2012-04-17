<?php
class Holmes extends Plugin
{
	
	/**
	 * Add stuff we need
	 **/
	public function action_init()
	{
		// self::create_tokens();
		
		$this->add_template( 'scopes', dirname($this->get_file()) . '/scopes.php' );
		$this->add_template( 'scope', dirname($this->get_file()) . '/scope.php' );
		
	}
	
	/**
	 * Create ACL tokens
	 **/
	private static function create_tokens()
	{
		ACL::create_token( 'manage_scopes', _t('Manage scopes'), 'Administration', false );
		
		$group = UserGroup::get_by_name( 'admin' );
		$group->grant( 'manage_scopes' );
	}
	
	/**
	 * Destroy ACL tokens
	 **/
	private static function destroy_tokens()
	{
		ACL::destroy_token( 'manage_scopes' );
	}
	
	public function action_plugin_activation( $file )
	{
		if ( $file == str_replace( '\\','/', $this->get_file() ) ) {
			self::create_tokens();
		}
	}

	public function action_plugin_deactivation( $file )
	{
		if ( $file == str_replace( '\\','/', $this->get_file() ) ) {
			# delete default access token
			self::destroy_tokens();
		}
	}
	
	/**
	 * Simplistic solution to just provide a bunch of scopes
	 */
	public function filter_get_scopes($scopes)
	{
		$our_scopes = $this->get_our_scopes();
		
		$scopeid = 93200; // starting ID
		foreach($our_scopes as $our_scope) {
			$scope = new StdClass();
			$scope->id = $scopeid++;
			$scope->name = $our_scope->name;
			$scope->priority = 15; // Make this configurable
			
			$criteria = array( 'request' => $our_scope->rule );
			
			if( isset( $our_scope->params ) )
			{
				// $criteria['generic_params'] = array();
				foreach( $our_scope->params as $param => $value )
				{
					$criteria[ $param ] = $value;
				}
			}
			
			// Utils::debug( $criteria );
			
			$scope->criteria = $criteria;
			$scopes[] = $scope;
		}
		
		return $scopes;
	}
	
	/**
	 * Get a list of active scopes, according to Habari
	 **/
	public function get_active_scopes()
	{
		$scopes = DB::get_results( 'SELECT * FROM {scopes} ORDER BY name ASC;' );
		$scopes = Plugins::filter( 'get_scopes', $scopes );
		
		return $scopes;
	}
	
	/**
	 * Get a list of available scopes 
	 **/
	public function get_available_scopes()
	{
		$rules = RewriteRules::get_active();
		
		$scopes = array();
		
		foreach( $rules as $rule )
		{
			switch( $rule->name )
			{
				default:
					$name = $rule->name;
					$break;
			}
			
			$scopes[$rule->name] = $name;
		}
		
		return $scopes;
	}
	
	/**
	 * Gets a list of the scopes created/managed by Holmes
	 **/
	public function get_our_scopes()
	{
		// Options::delete( 'homes__scopes' );
		$scopes = Options::get( 'homes__scopes' );
		
		return $scopes;
	}
	
	/**
	 * Ensure our scope criteria are implemented 
	 **/
	public function filter_get_blocks( $blocks, $area, $scope, $theme )
	{
		// Utils::debug( $blocks, $area, $scope );
		
		return $blocks;
	}
	
	/**
	 * Gets a Holmes-managed scope
	 **/
	public function get_scope( $slug )
	{
		$scopes = $this->get_our_scopes();
		
		$scope = $scopes[ $slug ];
		
		return $scope;
	}
	
	/**
	 * Save a scope to our internal list
	 **/
	public function save_scope( $scope )
	{		
		$slug = Utils::slugify( $scope->name );
		
		$scope->slug = $slug;
		
		$scopes = $this->get_our_scopes();
		
		$scopes[ $slug ] = $scope;
				
		Options::set( 'homes__scopes', $scopes );
		
		return true;
	}
	
	/**
	 * Add a scope to our internal list
	 **/
	public function add_scope( $scope )
	{
		$slug = Utils::slugify( $scope->name );
		
		$scope->slug = $slug;
		
		
		$scopes = $this->get_our_scopes();
		
		$scopes[ $slug ] = $scope;
		
		Options::set( 'homes__scopes', $scopes );
		
		return true;
	}
	
	/**
	 * Deletes a scope from our internal list
	 **/
	public function delete_scope( $scope )
	{
		$slug = Utils::slugify( $scope->name );
		
		$scopes = $this->get_our_scopes();
				
		unset( $scopes[ $slug ] );
				
		Options::set( 'homes__scopes', $scopes );
		
		return true;
	}
	
	/**
	 * limit access to our admin panel
	 **/
	public function filter_admin_access_tokens( array $require_any, $page )
	{		
		switch ( $page ) {
			case 'scope':
			case 'scopes':
				$require_any = array( 'manage_scopes' => true );
				break;
		}
		return $require_any;
	}
	
	/**
	 * add our admin panel to the menu
	 **/
	public function filter_adminhandler_post_loadplugins_main_menu( array $menu )
	{
		$item_menu = array( 'manage_scopes' => array(
			'url' => URL::get( 'admin', 'page=scopes'),
			'title' => _t('Manage scopes'),
			'text' => _t('Scopes'),
			'hotkey' => 'S',
			'selected' => false
		) );

		$slice_point = array_search( 'plugins', array_keys( $menu ) ); // Element will be inserted before "groups"
		$pre_slice = array_slice( $menu, 0, $slice_point);
		$post_slice = array_slice( $menu, $slice_point);

		$menu = array_merge( $pre_slice, $item_menu, $post_slice );

		return $menu;
	}
	
	/**
	 * Handle the form for adding a scope
	 **/
	public function filter_add_scope_form( $save_form, $form )
	{
		// Utils::debug( $form->scope_name->value );
		
		$scope = new StdClass();
		$scope->name = $form->scope_name->value;
		$scope->rule = $form->scope_rule->value;
		
		if( $this->add_scope( $scope ) )
		{
			Utils::redirect();
		}
		
		return $save_form || false;
	}
	
	/**
	 * handle the scopes management page
	 **/
	public function action_admin_theme_get_scopes( AdminHandler $handler, Theme $theme )
	{
		// Pass a list of scopes
		$theme->assign( 'active_scopes', $this->get_our_scopes() );
		$theme->assign( 'available_scopes', $this->get_available_scopes() );
		
		// Build our form for adding a scope
		$add_form = new FormUI( 'add_scope' );
		$add_form->append( 'text', 'scope_name', 'null:null', _t('Scope name', 'holmes') );
	
		$add_form->append( 'select', 'scope_rule', 'null:null', _t( 'Scope rule') );
		$add_form->scope_rule->options = $this->get_available_scopes();
	
		$add_form->append( 'submit', 'add', _t('Add', 'holmes') );
		$add_form->on_success( 'add_scope_form' );
		
		$theme->assign( 'add_form', $add_form );
		
		// Utils::debug( $theme );
		$theme->display( 'scopes' );
		

		// End everything
		exit;
	}
	
	/**
	 * This is just an alias function 
	 **/
	public function action_admin_theme_post_scopes( AdminHandler $handler, Theme $theme )
	{
		return $this->action_admin_theme_get_scopes( $handler, $theme );
	}
	
	/**
	 * Handle the form for configuring a scope
	 **/
	public function filter_scope_config_form( $save_form, $form, $scope )
	{
		// Utils::debug( $form->scope_name->value );
		
		$scope->name = $form->scope_name->value;
		
		$params = $this->get_rule_parameters( $scope->rule );
		
		foreach( $params as $param )
		{
			$value = $form->{$param}->value;
			if( $value != '')
			{
				$scope->params->{$param} = $value;
			}
		}
				
		if( $this->save_scope( $scope ) )
		{
			Utils::redirect();
		}
		
		return $save_form || false;
	}
	
	/**
	 * Gets the parameters available for a rewrite rule 
	 **/
	public function get_rule_parameters( $rule )
	{
		$rule = RewriteRules::by_name( $rule );
		$rule = $rule[0];
		
		$search = '/\([^\]*\$+[^\(\)]*\)/';
		$search = '{\$\w+}';
		// $search = '';
		$replace = '';

		$return_url = $rule->build_str;
		
		$matches = array();
		$return_url = preg_match_all( $search, $rule->build_str, $matches );
		
		$params = array();
				
		foreach( $matches[0] as $param )
		{
			$params[] = substr( $param, 1 );
		}
		
		// $params = URL::extract_args( $rule );
		
		return $params;
	}
	
	/**
	 * Generate the scope configuration form 
	 **/
	public function get_scope_config_form( $scope )
	{
		$form = new FormUI( 'config_scope' );
		
		$form->append( 'text', 'scope_name', 'null:null', _t('Scope name', 'holmes') );
		$form->scope_name->value = $scope->name;

		foreach( $this->get_rule_parameters( $scope->rule ) as $param )
		{
			$form->append( 'text', $param, 'null:null', $param );
			if( isset( $scope->params->{$param} ) )
			{
				$form->{$param}->value = $scope->params->{$param};
			}
		}
		
		$form->append( 'submit', 'save', _t('Save', 'holmes') );
		$form->on_success( 'scope_config_form', $scope );
		
		return $form;
	}
	
	/**
	 * handle the scope configuration page
	 **/
	public function action_admin_theme_get_scope( AdminHandler $handler, Theme $theme )
	{
		$scope = $this->get_scope( Controller::get_var( 'scope' ) );
		
		if( Controller::get_var( 'action' ) == 'delete' )
		{
			if( $this->delete_scope( $scope ) )
			{
				Session::notice( sprintf( _t( 'The scope %s has been deleted.' ), $scope->name ));
				Utils::redirect( URL::get( 'admin', 'page=scopes') );
			}
			$this->delete_scope( $scope );
		}
		
		$theme->assign( 'scope', $scope );		
		$theme->assign( 'config_form', $this->get_scope_config_form( $scope ) );
		
		// Utils::debug( $theme );
		$theme->display( 'scope' );
		
		// End everything
		exit;
	}
	
	/**
	 * This is just an alias function 
	 **/
	public function action_admin_theme_post_scope( AdminHandler $handler, Theme $theme )
	{
		return $this->action_admin_theme_get_scope( $handler, $theme );
	}
	

}
?>
