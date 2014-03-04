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
use DreamFactory\Yii\Utility\Validate;

/**
 * @var WebController $this
 * @var ActivateForm  $model
 * @var CActiveForm   $form
 */

Validate::register(
	'form#activate-form',
	array(
		 'ignoreTitle'    => true,
		 'errorClass'     => 'error',
		 'errorPlacement' => 'function(error,element){error.appendTo(element.closest("div.form-group"));error.css("margin","-10px 0 0");}',
		 'rules'          => array(
			 'ActivateForm[username]' => array(
				 'required'  => true,
				 'minlength' => 5,
			 ),
			 'ActivateForm[password]' => array(
				 'required' => true,
			 ),
		 ),
	)
);
?>
<div class="container" id="formbox">
	<h2>DSP Activation</h2>

	<p>To activate this DSP, please enter your <a href="https://www.dreamfactory.com">www.dreamfactory.com</a>
		email and password. You will automatically be made an admin user of this DSP. You may modify this user or
		add more users once you've logged in.
	</p>

	<?php $form = $this->beginWidget(
		'CActiveForm',
		array(
			 'id'                     => 'activate-form',
			 'enableClientValidation' => true,
			 'clientOptions'          => array(
				 'validateOnSubmit' => true,
			 ),
		)
	); ?>

	<div class="form-group">
		<label for="ActivateForm_username" class="sr-only">Email Address</label>

		<div class="input-group">
			<span class="input-group-addon bg_dg"><i class="fa fa-envelope fa-fw"></i></span>

			<input tabindex="1" class="form-control email required" autofocus type="email" id="ActivateForm_username"
				   name="ActivateForm[username]" placeholder="Email Address"
				   value="<?php echo( $model->username ? $model->username : '' ); ?>" />
		</div>
	</div>

	<div class="form-group">
		<label for="ActivateForm_password" class="sr-only">Password</label>

		<div class="input-group">
			<span class="input-group-addon bg_ly"><i class="fa fa-lock fa-fw"></i></span>

			<input tabindex="2" class="form-control password required" type="password" id="ActivateForm_password"
				   name="ActivateForm[password]" placeholder="Password" />
		</div>
	</div>

	<?php echo $form->errorSummary( $model ); ?>

	<div class="form-buttons">
		<button type="submit" class="btn btn-success pull-right">Activate</button>
	</div>

	<?php $this->endWidget(); ?>

</div>
<script type="text/javascript">
</script>