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
/* @var $this WebController */
/* @var $error array */

$this->pageTitle = Yii::app()->name . ' - Error';
$this->breadcrumbs = array(
	'Error',
);
?>
<div class="container">
	<h2><?php echo $this->pageTitle; ?></h2>
	<p>This application has experienced a fatal error.
		If you would like to report this error,
		please contact our <a href="//www.dreamfactory.com/developers/support">support</a>.
	</p>
	<h4>Error <?php echo $code; ?></h4>

	<div class="error">
		<?php echo CHtml::encode( $message ); ?>
	</div>
</div>