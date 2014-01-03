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
use DreamFactory\Yii\Utility\Validate;

/**
 * @var WebController $this
 * @var LoginForm     $model
 * @var bool          $redirected
 * @var CActiveForm   $form
 */

Validate::register(
		'form#login-form',
		array(
			'ignoreTitle'    => true,
			'errorClass'     => 'error',
			'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
		)
);

CHtml::$errorSummaryCss = 'alert alert-danger';

$_headline = 'Login Required!';
?>
<div class="container" id="formbox">
	<h2><?php echo $_headline; ?></h2>

	<p>In order to proceed to the requested resource, you must be logged in.</p>

	<?php
	$form = $this->beginWidget(
				 'CActiveForm',
				 array(
					 'id'                     => 'login-form',
					 'enableClientValidation' => true,
					 'clientOptions'          => array(
						 'validateOnSubmit' => true,
					 ),
				 )
	);
	?>

	<?php echo $form->errorSummary(
					$model,
					'<strong>Please check your entries...</strong>',
					null,
					array( 'style' => 'margin-bottom: 15px;' )
	); ?>

	<input type="hidden" name="login-only" value="<?php echo $redirected ? 1 : 0; ?>">

	<div class="form-group">
		<label for="LoginForm_username" class="sr-only">DSP User Email Address</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-envelope fa-fw"></i></span>
			<input tabindex="1" class="form-control email" autofocus type="email" id="LoginForm_username" name="LoginForm[username]"
				placeholder="DSP User Email Address" />
		</div>
	</div>
	<div class="form-group">
		<label for="LoginForm_password" class="sr-only">Password</label>

		<div class="input-group">
			<span class="input-group-addon bg_ly"><i class="fa fa-lock fa-fw"></i></span>
			<input tabindex="3" class="form-control password" type="password" id="LoginForm_password" name="LoginForm[password]"
				placeholder="Password" />
		</div>
	</div>
	<div class="form-buttons">
		<button type="submit" class="btn btn-success pull-right">Login</button>
		<button type="button" id="btn-home" class="btn btn-default pull-left">Home</button>
	</div>

	<?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
	jQuery(function ($) {
		$('#btn-home').on('click', function (e) {
			e.preventDefault();
			window.location.href = '/';
		});
	});
</script>