<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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
use DreamFactory\Platform\Exceptions\RestException;
use DreamFactory\Platform\Interfaces\PlatformStates;
use DreamFactory\Platform\Resources\System\Config;
use DreamFactory\Platform\Resources\User\Password;
use DreamFactory\Platform\Resources\User\Profile;
use DreamFactory\Platform\Resources\User\Register;
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
use Kisma\Core\Utility\Storage;

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
					'upgradeSchema',
					'authorize',
					'remoteLogin',
					'maintenance',
					'welcome',
					'securityQuestion',
					'register',
					'confirmRegister',
					'confirmInvite',
					'confirmPassword',
				),
				'users'   => array( '*' ),
			),
			//	Allow authenticated users access to init commands
			array(
				'allow',
				'actions' => array(
					'upgrade',
					'password',
					'profile',
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
					$this->redirect( $this->_getRedirectUrl( 'initSystem' ) );
					break;

				case PlatformStates::SCHEMA_REQUIRED:
					$this->redirect( $this->_getRedirectUrl( 'upgradeSchema' ) );
					break;

				case PlatformStates::UPGRADE_REQUIRED:
					$this->redirect( $this->_getRedirectUrl( 'upgrade' ) );
					break;

				case PlatformStates::ADMIN_REQUIRED:
					if ( Fabric::fabricHosted() )
					{
						$this->redirect( $this->_getRedirectUrl( 'activate' ) );
					}
					else
					{
						$this->redirect( $this->_getRedirectUrl( 'initAdmin' ) );
					}
					break;

				case PlatformStates::DATA_REQUIRED:
					$this->redirect( $this->_getRedirectUrl( 'initData' ) );
					break;

				case PlatformStates::WELCOME_REQUIRED:
					$this->redirect( $this->_getRedirectUrl( 'welcome' ) );
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

	protected function _initSystemSplash()
	{
		$this->render(
			'_splash',
			array(
				 'for' => PlatformStates::INIT_REQUIRED,
			)
		);
	}

	/**
	 * Activates the system
	 */
	public function actionInitSystem()
	{
		// put our schema in the empty database
		SystemManager::initSchema();

		// let the system figure out the next state
		$this->redirect( '/' );
	}

	/**
	 * Displays the system init schema page
	 */
	public function actionUpgradeSchema()
	{
		$_model = new UpgradeSchemaForm();

		if ( isset( $_POST, $_POST['UpgradeSchemaForm'] ) )
		{
			$_model->attributes = $_POST['UpgradeSchemaForm'];

			if ( $_model->validate() )
			{
				SystemManager::upgradeSchema();
				$this->redirect( '/' );

				return;
			}
			else
			{
				// Log::debug( 'Failed validation' );
			}

			$this->refresh();
		}

		$this->render(
			'upgradeSchema',
			array(
				 'model' => $_model
			)
		);
	}

	/**
	 * Adds the first admin from a form
	 */
	public function actionInitAdmin()
	{
		if ( $this->_activated )
		{
			$this->redirect( '/' );
		}

		//	Clear the skipped flag...
		Pii::setState( 'app.registration_skipped', false );

		$_model = new InitAdminForm();

		if ( isset( $_POST, $_POST['InitAdminForm'] ) )
		{
			$_model->attributes = $_POST['InitAdminForm'];

			if ( $_model->validate() )
			{
				try
				{
					SystemManager::initAdmin( $_model->attributes );
					$this->redirect( '/' );

					return;
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( 'email', $_ex->getMessage() );
				}
			}
		}

		$this->render(
			'initAdmin',
			array(
				 'model' => $_model
			)
		);
	}

	/**
	 * First-time hosted activation page,
	 * Adds the first admin, based on DF user authentication
	 */
	public function actionActivate()
	{
		if ( $this->_activated )
		{
			$this->redirect( '/' );
		}

		$_model = new ActivateForm();

		if ( isset( $_POST, $_POST['ActivateForm'] ) )
		{
			$_model->attributes = $_POST['ActivateForm'];

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() && $_model->activate() )
			{
				try
				{
					SystemManager::initAdmin();
					$this->redirect( $this->_getRedirectUrl() );

					return;
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( 'username', $_ex->getMessage() );
				}
			}
			else
			{
				$_model->addError( 'username', 'Invalid username and password combination.' );
			}
		}

		$this->render(
			'activate',
			array(
				 'model' => $_model,
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
	 * Displays the login page
	 */
	public function actionLogin( $redirected = false )
	{
		if ( !Pii::guest() )
		{
			$this->redirect( '/' );
		}

		$_model = new LoginForm();

		// collect user input data
		if ( isset( $_POST, $_POST['LoginForm'] ) )
		{
			$_model->attributes = $_POST['LoginForm'];

			if ( 1 == Option::get( $_POST, 'forgot', 0 ) )
			{
				try
				{
					$_result = Password::passwordReset( $_model->username );
					$_question = Option::get( $_result, 'security_question' );
					if ( !empty( $_question ) )
					{
						Yii::app()->user->setFlash( 'security-email', $_model->username );
						Yii::app()->user->setFlash( 'security-question', $_question );
						$this->redirect( $this->_getRedirectUrl( 'securityQuestion' ) );

						return;
					}
					elseif ( Option::getBool( $_result, 'success' ) )
					{
						Yii::app()->user->setFlash( 'login-form', 'A password reset confirmation has been sent to this email.' );
					}
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( 'username', $_ex->getMessage() );
				}
			}
			//	Validate user input and redirect to the previous page if valid
			elseif ( $_model->validate() )
			{
				$this->redirect( $this->_getRedirectUrl() );

				return;
			}
		}

		$_providers = array();

		$this->render(
			'login',
			array(
				 'model'          => $_model,
				 'activated'      => $this->_activated,
				 'redirected'     => $redirected,
				 'loginProviders' => $_providers,
			)
		);
	}

	public function actionSecurityQuestion()
	{
		if ( !Pii::guest() )
		{
			$this->redirect( '/' );
		}

		$_model = new SecurityForm();

		// collect user input data
		if ( isset( $_POST, $_POST['SecurityForm'] ) )
		{
			$_model->attributes = $_POST['SecurityForm'];

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() )
			{
				try
				{
					$_identity = Password::changePasswordBySecurityAnswer(
						$_model->email,
						$_model->answer,
						$_model->password,
						true,
						true
					);

					if ( Pii::user()->login( $_identity ) )
					{
						$this->redirect( $this->_getRedirectUrl() );

						return;
					}

					$_model->addError( null, 'Password changed successfully, but failed to automatically login.' );
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( 'answer', $_ex->getMessage() );
				}
			}
		}
		else
		{
			// this is initially redirected from login screen - forgot password scenario
			$_model->email = Yii::app()->user->getFlash( 'security-email' );
			$_model->question = Yii::app()->user->getFlash( 'security-question' );
			if ( empty( $_model->email ) || empty( $_model->question ) )
			{
				$_model->addError( 'answer', 'Error retrieving security question.' );
			}
		}

		$this->render(
			'securityQuestion',
			array(
				 'model' => $_model,
			)
		);
	}

	/**
	 * First-time Welcome page
	 */
	public function actionWelcome()
	{
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
			$this->redirect( $this->_getRedirectUrl() );
		}

		$_model = new SupportForm();

		// collect user input data
		if ( isset( $_POST, $_POST['SupportForm'] ) )
		{
			$_model->setAttributes( $_POST['SupportForm'] );

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() )
			{
				try
				{
					SystemManager::registerPlatform( $_user, $_model->getSkipped() );
					$this->redirect( $this->_getRedirectUrl() );

					return;
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( null, $_ex->getMessage() );
				}
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
	 * Adds the registering user from a form
	 */
	public function actionRegister()
	{
		if ( !Pii::guest() )
		{
			$this->redirect( '/' );
		}

		$_model = new RegisterUserForm();

		/** @var $_config Config */
		if ( false === ( $_config = Config::getOpenRegistration() ) )
		{
			throw new BadRequestException( "Open registration for users is not currently enabled for this system." );
		}

		$_viaEmail = ( null !== Option::get( $_config, 'open_reg_email_service_id' ) );
		$_model->setViaEmail( $_viaEmail );

		if ( isset( $_POST, $_POST['RegisterUserForm'] ) )
		{
			$_model->attributes = $_POST['RegisterUserForm'];

			if ( $_model->validate() )
			{
				try
				{
					$_result = Register::userRegister( $_model->attributes, true, true );

					if ( $_viaEmail )
					{
						if ( Option::getBool( $_result, 'success' ) )
						{
							Yii::app()->user->setFlash( 'register-user-form', 'A registration confirmation has been sent to this email.' );
						}
					}
					else
					{
						// result should be identity
						if ( Pii::user()->login( $_result ) )
						{
							$this->redirect( $this->_getRedirectUrl() );

							return;
						}

						$_model->addError( null, 'Registration successful, but failed to automatically login.' );
					}
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( null, $_ex->getMessage() );
				}
			}
		}

		$this->render(
			'register',
			array(
				 'model' => $_model,
				 'backUrl' => $this->_getRedirectUrl()
			)
		);
	}

	public function actionConfirmRegister()
	{
		$this->_userConfirm( 'register' );
	}

	public function actionConfirmInvite()
	{
		$this->_userConfirm( 'invite' );
	}

	public function actionConfirmPassword()
	{
		$this->_userConfirm( 'password' );
	}

	protected function _userConfirm( $reason )
	{
		if ( !Pii::guest() )
		{
			$this->redirect( '/' );
		}

		$_model = new ConfirmUserForm();

		// collect user input data
		if ( isset( $_POST, $_POST['ConfirmUserForm'] ) )
		{
			$_model->attributes = $_POST['ConfirmUserForm'];

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() )
			{
				try
				{
					switch ( $reason )
					{
						case 'register':
							$_identity = Register::userConfirm(
								$_model->email,
								$_model->code,
								$_model->password,
								true,
								true
							);
							break;
						default:
							$_identity = Password::changePasswordByCode(
								$_model->email,
								$_model->code,
								$_model->password,
								true,
								true
							);
							break;
					}

					if ( Pii::user()->login( $_identity ) )
					{
						$this->redirect( $this->_getRedirectUrl() );

						return;
					}

					$_model->addError( null, 'Password changed successfully, but failed to automatically login.' );
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( 'email', $_ex->getMessage() );
				}
			}
		}

		$this->render(
			'confirm',
			array(
				 'model'  => $_model,
				 'reason' => $reason,
			)
		);
	}

	/**
	 * Displays the password reset page
	 */
	public function actionPassword()
	{
		if ( Pii::guest() )
		{
			$this->_redirectError( 'You must be logged in to change your password.' );
		}

		$_model = new PasswordForm();

		// collect user input data
		if ( isset( $_POST, $_POST['PasswordForm'] ) )
		{
			$_model->attributes = $_POST['PasswordForm'];

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() )
			{
				try
				{
					$_userId = Session::getCurrentUserId();
					$_result = Password::changePassword( $_userId, $_model->old_password, $_model->new_password );

					if ( Option::getBool( $_result, 'success' ) )
					{
						Yii::app()->user->setFlash( 'password-form', 'Your password has been successfully updated.' );
					}
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( null, $_ex->getMessage() );
				}
			}
		}

		$this->render(
			'password',
			array(
				 'model'   => $_model,
				 'backUrl' => $this->_getRedirectUrl()
			)
		);
	}

	/**
	 * Updates the user profile from a form
	 */
	public function actionProfile()
	{
		if ( Pii::guest() )
		{
			$this->_redirectError( 'You must be logged in to change your profile.' );
		}

		$_model = new ProfileForm();

		if ( isset( $_POST, $_POST['ProfileForm'] ) )
		{
			$_model->attributes = $_POST['ProfileForm'];

			if ( $_model->validate() )
			{
				try
				{
					$_userId = Session::getCurrentUserId();
					$_result = Profile::changeProfile( $_userId, $_model->attributes );

					if ( Option::getBool( $_result, 'success' ) )
					{
						Yii::app()->user->setFlash( 'profile-form', 'Your profile has been successfully updated.' );
					}
				}
				catch ( \Exception $_ex )
				{
					$_model->addError( null, $_ex->getMessage() );
				}
			}
		}
		else
		{
			$_userId = Session::getCurrentUserId();
			$_model->attributes = Profile::getProfile( $_userId );
		}

		$this->render(
			'profile',
			array(
				 'model' => $_model,
				 'backUrl' => $this->_getRedirectUrl()
			)
		);
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

	protected function _getRedirectUrl( $action = null )
	{
		if ( !empty( $action ) )
		{
			// forward to that action page
			$_returnUrl = '/' . $this->id . '/' . $action;
		}
		else
		{
			// redirect to back from which you came
			if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
			{
				// or take me home
				$_returnUrl = Pii::url( $this->id . '/index' );
			}
		}

		return $_returnUrl;
	}
}
