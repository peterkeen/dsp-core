<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Oasys;
use DreamFactory\Oasys\Stores\FileSystem;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Exceptions\ForbiddenException;
use DreamFactory\Platform\Interfaces\PlatformStates;
use DreamFactory\Platform\Resources\User\Session;
use DreamFactory\Platform\Services\AsgardService;
use DreamFactory\Platform\Services\SystemManager;
use DreamFactory\Platform\Utility\Fabric;
use DreamFactory\Platform\Utility\ResourceStore;
use DreamFactory\Platform\Yii\Models\Provider;
use DreamFactory\Platform\Yii\Models\User;
use DreamFactory\Yii\Controllers\BaseWebController;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Interfaces\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * WebController.php
 * The initialization and set-up controller
 */
class WebController extends BaseWebController
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_STARTUP_APP = '/launchpad/index.html';

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var bool
	 */
	protected $_activated = false;
	/**
	 * @var bool
	 */
	protected $_autoLogged = false;
	/**
	 * @var string
	 */
	protected $_remoteError = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Initialize
	 */
	public function init()
	{
		parent::init();

		$this->defaultAction = 'index';
		$this->_activated = SystemManager::activated();

		//	Remote login errors?
		$_error = FilterInput::request( 'error', null, FILTER_SANITIZE_STRING );
		$_message = FilterInput::request( 'error_description', null, FILTER_SANITIZE_STRING );

		if ( !empty( $_error ) )
		{
			$this->_remoteError = $_error . ( !empty( $_message ) ? ' (' . $_message . ')' : null );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	/**
	 * {@InheritDoc}
	 */
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'actions' => array(
					'index',
					'login',
					'error',
					'activate',
					'initSystem',
					'initSchema',
					'initData',
					'initAdmin',
					'authorize',
					'remoteLogin',
					'maintenance',
					'welcome',
				),
				'users'   => array( '*' ),
			),
			//	Allow authenticated users access to init commands
			array(
				'allow',
				'actions' => array(
					'upgrade',
					'upgradeSchema',
					'initAdmin',
					'environment',
					'metrics',
					'fileTree',
					'logout',
				),
				'users'   => array( '@' ),
			),
			//	Deny all others access to init commands
			array(
				'deny',
			),
		);
	}

	/**
	 * Maintenance screen
	 */
	public function actionMaintenance()
	{
		$this->layout = 'maintenance';
		$this->render( 'maintenance' );
		die();
	}

	protected function _initSystemSplash()
	{
		$this->render(
			 '_splash',
			 array(
				 'for' => PlatformStates::INIT_REQUIRED,
			 )
		);

		$this->actionInitSystem();
	}

	public function actionActivate()
	{
		//	Clear the skipped flag...
		Pii::setState( 'app.registration_skipped', false );

		$_model = new \LoginForm();

		//	Came from login form? Don't do drupal auth, do dsp auth
		$_fromLogin = ( 0 != Option::get( $_POST, 'login-only', 0 ) );

		//	Did we come because we need to log in?
		if ( !Pii::postRequest() && $this->_activated )
		{
			if ( null !== ( $_returnUrl = Pii::user()->getReturnUrl() ) && 200 == Option::server( 'REDIRECT_STATUS' ) )
			{
				$this->actionLogin( true );

				return;
			}
		}
		else
		{
			if ( 1 == Option::get( $_POST, 'skipped', 0 ) )
			{
				Pii::setState( 'app.registration_skipped', true );
				$this->redirect( '/' . $this->id . '/initAdmin' );

				return;
			}
		}

		if ( isset( $_POST, $_POST['LoginForm'] ) )
		{
			$_model->attributes = $_POST['LoginForm'];
			$_model->setDrupalAuth( ( 0 == Option::get( $_POST, 'login-only', 0 ) ) );

			if ( !empty( $_model->username ) && !empty( $_model->password ) )
			{
				//	Validate user input and redirect to the previous page if valid
				if ( $_model->validate() && $_model->login() )
				{
					if ( !$this->_activated )
					{
						SystemManager::initAdmin();
					}

					if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
					{
						$_returnUrl = Pii::url( $this->id . '/index' );
					}

					$this->redirect( $_returnUrl );

					return;
				}
				else
				{
//					$_model->addError( 'username', 'Invalid user name and password combination.' );

					//	Came from login form? Don't do drupal auth, do dsp auth
					if ( $_fromLogin )
					{
						$this->actionLogin( true );

						return;
					}
				}
			}
			else
			{
				if ( !$this->_activated )
				{
					$this->redirect( '/' . $this->id . '/initAdmin' );
				}
				else
				{
					$this->redirect( '/' . $this->id . '/index' );
				}
			}
		}

		$this->render(
			 'activate',
			 array(
				 'model'     => $_model,
				 'activated' => $this->_activated,
			 )
		);
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionIndex()
	{
		try
		{
			$_error = false;
			$_state = SystemManager::getSystemState();

			if ( !$this->_activated && $_state != PlatformStates::INIT_REQUIRED )
			{
				$_state = PlatformStates::ADMIN_REQUIRED;
			}

			if ( !empty( $this->_remoteError ) )
			{
				$_error = 'error=' . urlencode( $this->_remoteError );
			}

			switch ( $_state )
			{
				case PlatformStates::READY:
					$_defaultApp = Pii::getParam( 'dsp.default_app', static::DEFAULT_STARTUP_APP );

					//	Try local launchpad
					if ( is_file( \Kisma::get( 'app.app_path' ) . $_defaultApp ) )
					{
						if ( $_error )
						{
							$_defaultApp = $_defaultApp . ( false !== strpos( $_defaultApp, '?' ) ? '&' : '?' ) . $_error;
						}

						$this->redirect( $_defaultApp );
					}

					//	Fall back to this app default site
					$this->render( 'index' );
					break;

				case PlatformStates::INIT_REQUIRED:
					$this->redirect( '/' . $this->id . '/initSystem' );
					break;

				case PlatformStates::ADMIN_REQUIRED:
					$this->redirect( '/' . $this->id . '/activate' );
					break;

				case PlatformStates::SCHEMA_REQUIRED:
				case PlatformStates::UPGRADE_REQUIRED:
					$this->redirect( '/' . $this->id . '/upgradeSchema' );
					break;

				case PlatformStates::DATA_REQUIRED:
					$this->redirect( '/' . $this->id . '/initData' );
					break;
			}
		}
		catch ( \Exception $_ex )
		{
			die( $_ex->getMessage() );
		}
	}

	/**
	 * Error action
	 */
	public function actionError()
	{
		if ( null !== ( $_error = Pii::currentError() ) )
		{
			if ( Pii::ajaxRequest() )
			{
				echo $_error['message'];

				return;
			}

			$this->render( 'error', $_error );
		}
	}

	/**
	 * First-time Welcome page
	 */
	public function actionWelcome()
	{
		if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
		{
			$_returnUrl = Pii::url( $this->id . '/index' );
		}

		//	User cool too?
		if ( null === ( $_user = ResourceStore::model( 'user' )->findByPk( Session::getCurrentUserId() ) ) )
		{
			throw new ForbiddenException();
		}

		/**
		 * If request contains a "force_remove=1" parameter,
		 * remove the registration file and redirect
		 */
		if ( '1' == FilterInput::get( INPUT_GET, 'force_remove', 0 ) )
		{
			Log::debug( 'Forced removal of registration marker requested.' );
			SystemManager::registerPlatform( $_user, false, true );
			$this->redirect( $_returnUrl );
		}

		$_model = new SupportForm();

		// collect user input data
		if ( isset( $_POST['SupportForm'] ) )
		{
			$_model->setAttributes( $_POST['SupportForm'] );

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() )
			{
				SystemManager::registerPlatform( $_user, $_model->getSkipped() );

				$this->redirect( $_returnUrl );

				return;
			}

			$_model->addError( 'Problem', 'Registration System Unavailable' );
		}

		$this->render(
			 'welcome',
			 array(
				 'model' => $_model,
			 )
		);
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin( $redirected = false )
	{
		if ( !Pii::guest() )
		{
			$this->redirect( '/' );
		}

		$_model = new \LoginForm();

		// collect user input data
		if ( isset( $_POST['LoginForm'] ) )
		{
			$_model->attributes = $_POST['LoginForm'];
			$_model->setDrupalAuth( ( 0 == Option::get( $_POST, 'login-only', 0 ) ) );

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() && $_model->login() )
			{
				if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
				{
					$_returnUrl = Pii::url( $this->id . '/index' );
				}

				$this->redirect( $_returnUrl );

				return;
			}
		}

		$this->render(
			 'login',
			 array(
				 'model'      => $_model,
				 'activated'  => $this->_activated,
				 'redirected' => $redirected,
			 )
		);
	}

	/**
	 * Activates the system
	 */
	public function actionInitSystem()
	{
		SystemManager::initSystem();
		$this->redirect( '/' );
	}

	/**
	 * Displays the system init schema page
	 */
	public function actionUpgradeSchema()
	{
		$_model = new InitSchemaForm();

		if ( isset( $_POST, $_POST['InitSchemaForm'] ) )
		{
			$_model->attributes = $_POST['InitSchemaForm'];

			if ( $_model->validate() )
			{
				SystemManager::upgradeSchema();
				$this->redirect( '/' );
			}
			else
			{
//				Log::debug( 'Failed validation' );
			}

			$this->refresh();
		}

		$this->render(
			 'initSchema',
			 array(
				 'model' => $_model
			 )
		);
	}

	/**
	 * Adds the first admin, based on DF authenticated login
	 */
	public function actionInitAdmin()
	{
		if ( $this->_activated )
		{
//			Log::debug( 'initAdmin activated' );
			SystemManager::initAdmin();
			$this->redirect( '/' );
		}

//		Log::debug( 'initAdmin NOT activated' );

		$_model = new InitAdminForm();

		if ( isset( $_POST, $_POST['InitAdminForm'] ) )
		{
			$_model->attributes = $_POST['InitAdminForm'];

			if ( $_model->validate() )
			{
				SystemManager::initAdmin();
				$this->redirect( '/' );
			}

			$this->refresh();
		}

		$this->render(
			 'initAdmin',
			 array(
				 'model' => $_model
			 )
		);
	}

	/**
	 * Displays the system init data page
	 */
	public function actionInitData()
	{
		// just do, no need to ask
//		$_model = new InitDataForm();
//
//		if ( isset( $_POST, $_POST['InitDataForm'] ) )
//		{
//			$_model->attributes = $_POST['InitDataForm'];
//
//			if ( $_model->validate() )
//			{
		SystemManager::initData();
		$this->redirect( '/' );
//			}
//
//			$this->refresh();
//		}
//
//		$this->render(
//			'initData',
//			array(
//				 'model' => $_model
//			)
//		);
	}

	/**
	 * @throws \Exception
	 */
	public function actionUpgrade()
	{
		if ( Fabric::fabricHosted() )
		{
			throw new \Exception( 'Fabric hosted DSPs can not be upgraded . ' );
		}

		/** @var \CWebUser $_user */
		$_user = \Yii::app()->user;
		// Create and login first admin user
		if ( !$_user->getState( 'df_authenticated' ) )
		{
			try
			{
				Session::checkSessionPermission( 'admin', 'system' );
			}
			catch ( \Exception $ex )
			{
				throw new \Exception( 'Upgrade requires admin privileges, logout and login with admin credentials . ' );
			}
		}

		$_current = SystemManager::getCurrentVersion();
		$_temp = SystemManager::getDspVersions();
		$_versions = array();
		foreach ( $_temp as $_version )
		{
			$_name = Option::get( $_version, 'name', '' );
			if ( version_compare( $_current, $_name, '<' ) )
			{
				$_versions[] = $_name;
			}
		}

		$_model = new UpgradeDspForm();
		$_model->versions = $_versions;

		if ( isset( $_POST, $_POST['UpgradeDspForm'] ) )
		{
			$_model->setAttributes( $_POST['UpgradeDspForm'], false );

			if ( $_model->validate() )
			{
				$_version = Option::get( $_versions, $_model->selected, '' );
				try
				{
					SystemManager::upgradeDsp( $_version );

					$this->redirect( '/' );
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( 'versions', $_ex->getMessage() );
				}
			}
		}

		$this->render(
			 'upgradeDsp',
			 array(
				 'model' => $_model
			 )
		);
	}

	/**
	 * Displays the environment page
	 */
	public function actionEnvironment()
	{
		$this->render( 'environment' );
	}

	/**
	 * Makes a file tree. Used exclusively by the snapshot service at this time.
	 *
	 * @param string $instanceName
	 * @param string $path
	 *
	 * @return string
	 */
	public function actionFileTree( $instanceName = null, $path = null )
	{
		$_data = array();

		$_path = Pii::getParam( 'storage_path' );

		if ( !empty( $_path ) )
		{
			$_objects = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $_path ), RecursiveIteratorIterator::SELF_FIRST );

			/** @var $_node \SplFileInfo */
			foreach ( $_objects as $_name => $_node )
			{
				if ( $_node->isDir() || $_node->isLink() || '.' == $_name || '..' == $_name )
				{
					continue;
				}

				$_cleanPath = str_ireplace( $_path, null, dirname( $_node->getPathname() ) );

				if ( empty( $_cleanPath ) )
				{
					$_cleanPath = '/';
				}

				$_data[$_cleanPath][] = basename( $_name );
			}
		}

		echo json_encode( $_data );
		Pii::end();
	}

	/**
	 * Get DSP metrics
	 */
	public function actionMetrics()
	{
		if ( !Fabric::fabricHosted() )
		{
			$_stats = AsgardService::getStats();
		}
		else
		{
			$_endpoint = Pii::getParam( 'cloud.endpoint' ) . '/metrics/dsp?dsp=' . urlencode( Pii::getParam( 'dsp.name' ) );

			Curl::setDecodeToArray( true );
			$_stats = Curl::get( $_endpoint );
		}

		if ( empty( $_stats ) )
		{
			throw new \CHttpException( HttpResponse::NotFound );
		}

		$this->layout = false;
		header( 'Content-type: application/json' );

		echo json_encode( $_stats );
		Pii::end();
	}

	/**
	 * Action for URL that the client redirects to when coming back from providers.
	 */
	public function actionRemoteLogin()
	{
		if ( null !== $this->_remoteError )
		{
			$this->_redirectError( $this->_remoteError );
		}

		if ( null === ( $_providerId = Option::request( 'pid' ) ) )
		{
			throw new BadRequestException( 'No remote login provider specified.' );
		}

		$this->layout = false;

		$_flow = FilterInput::request( 'flow', Flows::CLIENT_SIDE, FILTER_SANITIZE_NUMBER_INT );

		//	Check local then global...
		if ( null === ( $_providerModel = Provider::model()->byPortal( $_providerId )->find() ) )
		{
			/** @var Provider $_providerModel */
			$_providerModel = Fabric::getProviderCredentials( $_providerId );

			if ( empty( $_providerModel ) )
			{
				throw new BadRequestException( 'The provider "' . $_providerId . '" is not available.' );
			}
		}

		//	Set our store...
		Oasys::setStore( $_store = new FileSystem( $_sid = session_id() ) );

		$_config = Provider::buildConfig(
						   $_providerModel,
						   Pii::getState( $_providerId . '.user_config', array() ),
						   array(
							   'flow_type'    => $_flow,
							   'redirect_uri' => Curl::currentUrl( false ) . '?pid=' . $_providerId,
						   )
		);

		Log::debug( 'remote login config: ' . print_r( $_config, true ) );

		$_provider = Oasys::getProvider( $_providerId, $_config );

		if ( $_provider->handleRequest() )
		{
			//	Now let the user model figure out what to do...
			try
			{
				$_user = User::remoteLoginRequest( $_providerId, $_provider, $_providerModel );
				Log::debug( 'Remote login success: ' . $_user->email . ' (id#' . $_user->id . ')' );
			}
			catch ( \Exception $_ex )
			{
				Log::error( $_ex->getMessage() );

				//	No soup for you!
				$this->_redirectError( $_ex->getMessage() );
			}

			//	Go home baby!
			$this->redirect( '/' );
		}

		Log::error( 'Seems that the provider rejected the login...' );
		$this->_redirectError( 'Error during remote login sequence. Please try again.' );
	}

	/**
	 * Handle inbound redirect from various services
	 *
	 * @throws DreamFactory\Common\Exceptions\RestException
	 */
	public function actionAuthorize()
	{
		Log::debug( 'Inbound $REQUEST: ' . print_r( $_REQUEST, true ) );

		$_state = Storage::defrost( Option::request( 'state' ) );
		$_origin = Option::get( $_state, 'origin' );
		$_apiKey = Option::get( $_state, 'api_key' );

		Log::debug( 'Inbound state: ' . print_r( $_state, true ) );

		if ( empty( $_origin ) || empty( $_apiKey ) )
		{
			Log::error( 'Invalid request state.' );
			throw new BadRequestException();
		}

		if ( $_apiKey != ( $_testKey = sha1( $_origin ) ) )
		{
			Log::error( 'API Key mismatch: ' . $_apiKey . ' != ' . $_testKey );
			throw new ForbiddenException();
		}

		$_code = FilterInput::request( 'code', null, FILTER_SANITIZE_STRING );

		if ( !empty( $_code ) )
		{
			Log::debug( 'Inbound code received: ' . $_code . ' from ' . $_state['origin'] );
		}
		else
		{
			if ( null === Option::get( $_REQUEST, 'access_token' ) )
			{
				Log::error( 'Inbound request code missing.' );
				throw new RestException( HttpResponse::BadRequest );
			}
			else
			{
				Log::debug( 'Token received. Relaying to origin.' );
			}
		}

		$_redirectUri = Option::get( $_state, 'redirect_uri', $_state['origin'] );
		$_redirectUrl = $_redirectUri . ( false === strpos( $_redirectUri, '?' ) ? '?' : '&' ) . \http_build_query( $_REQUEST );

		Log::debug( 'Proxying request to: ' . $_redirectUrl );

		header( 'Location: ' . $_redirectUrl );
		exit();
	}

	/**
	 * @param string $message
	 * @param string $url
	 */
	protected function _redirectError( $message, $url = '/' )
	{
		$this->redirect( $url . '?error=' . urlencode( $message ) );
	}
}
