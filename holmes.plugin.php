<?php
class Holmes extends Plugin
{
	
	/**
	 * Simplistic solution to just provide a bunch of scopes
	 */
	public function filter_get_scopes($scopes)
		{
			$rules = RewriteRules::get_active();
			$scopeid = 40000;
			foreach($rules as $rule) {
				$scope = new StdClass();
				$scope->id = $scopeid++;
				$scope->name = $rule->name;
				$scope->priority = 15;
				$scope->criteria = array(
					array('request', $rule->name),
				);
				$scopes[] = $scope;
			}

			return $scopes;
		}
}
?>
