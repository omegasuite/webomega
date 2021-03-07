	<div class="list-content wrapper" style="background: none">

                    <center>
  {ERROR}
                    </center>

            <div style="float:left;">
            <div class="col-lg-6">
		<!-- BEGIN PASS_SUBMIT_BUTTON -->
                <section class="panel" style="height: 600px;">
                    <header class="panel-heading">
                        <h3>更改登录密码</h3>
                    </header>
                    <div class="panel-body">
		<form name="profile" action="index.php" method="post">
		<input type="hidden" name="module" id="module" value="users" />
		<input type="hidden" name="infochanged" value="password" />
		<input type="hidden" name="username" value="dummy" />
		<input type="hidden" name="norm_user_op" value="profile_changed" />
		<table border="0" cellpadding="3">
			<tr><td valign="top" align='right' class='c1'><b>{CURRENT_PASS}:</b></td><td><input type="password" name="pass0" size="15" maxlength="255"  class="form-control" /></td></tr>
			<tr><td valign="top" align='right' class='c1'><b>新密码:</b></td><td><input type="password" name="pass1" size="15" maxlength="255"  class="form-control" /></td></tr>
			<tr><td valign="top" align='right' class='c1'><b>确认密码:</b></td><td><input type="password" name="pass2" size="15" maxlength="255"  class="form-control" /></td></tr>
			<tr><td align=center colspan=3>{PASS_SUBMIT_BUTTON}</td></tr>
		<!-- END PASS_SUBMIT_BUTTON -->
<!-- BEGIN SRIMG -->
                        <tr><td valign="bottom" align="right" class="c1"><b>绑定手机:</b></td>
                        <td><img src={SRIMG} width="162" height="162"></td></tr>
                        <tr><td valign="bottom" align="right" class="c1"></td>
                           <td>注：微信扫一扫二维码绑定你的手机</td>
                        </tr>
<!-- END SRIMG -->
<!-- BEGIN WEIXINHEAD -->
                        <tr><td colspan=2>微信：<img src={WEIXINHEAD} width="98" height="98"></td></tr>
<!-- END WEIXINHEAD -->
		</table>
		</form>
                    </div>
                </section>
            </div>
            </div>

            <div style="float:right;">
            <div class="col-lg-6">
                <section class="panel" style="height: 600px;">
                    <header class="panel-heading">
                        <h3>修改个人信息</h3>
                    </header>
                    <div class="panel-body">
		<form name="profile" action={ACTION} method="post" enctype="multipart/form-data">
		<input type="hidden" name="module" id="module" value="users" />
		<input type="hidden" name="infochanged" value="personalinfo" />
		<input type="hidden" name="norm_user_op" value="profile_changed" />
		<table border="0" cellpadding="3" class='table-medium'>
			<tr><td valign="top" align='right' class='c1'><b>{INTRO_LBL}:</b></td><td align='left'>{INTRO}</td></tr>
			<tr><td valign="top" align='right' class='c1'><b>{STATE_LBL}:</b></td><td align='left'>{STATE_FIELD}</td></tr>
			<tr><td valign="top" align='right' class='c1'><b>{ZIP_LBL}:</b></td><td align='left'>{ZIP_FIELD}</td></tr>
			<tr><td valign="top" align='right' class='c1'><b>{CITY_LBL}:</b></td><td align='left'>{CITY_FIELD}</td></tr>
			<tr><td valign="top" align='right' class='c1'><b>{ADDRESS1_LBL}:</b></td><td align='left'>{ADDRESS1_FIELD}</td></tr>
			<tr><td valign="top" align='right' class='c1'><b>{PHONE_LBL}:</b></td><td align='left'>{PHONE}</td></tr>
			<tr><td valign="top" align='right' class='c1'><b>{EMAIL_LBL}:</b></td><td align='left'>{EMAIL_USER_FIELD}@{EMAIL_DOMAIN_FIELD}</td></tr>
			<tr><td align=center colspan=2>{SUBMIT_BUTTON}</td></tr>
		</table>
		</form>
                    </div>
                </section>
            </div>
            </div>
        </div>
