<div class="header-section">
     <div class="logo white-logo-bg hidden-xs hidden-sm">
          <a href="./index.php"><img src="{LOGO}" alt="" height=64></a>
      </div>

      <div class="icon-logo white-logo-bg hidden-xs hidden-sm">
                <i class="fa fa-outdent toggle-btn"></i>
      </div>
      <a class="toggle-btn top-toggle-btn"><i class="fa fa-outdent"></i></a>
      <div style="float: right;padding-right: 55px;" class="notification-wrap">
          <!--left notification start-->
          <div style="border: 1px solid #ccc;border-width: 0 0 0 1px;padding: 0 10px;" class="left-notification">
              <ul class="notification-menu">
                  <!--notification info start-->
                  <li>
			<input class="form-control info-number" type="text" placeholder="扫二维码开始工作" size="15" height=24px maxlength="512" onchange="scanAct(this.value);"  />
                  </li>
              <!--notification info end-->
              </ul>
          </div>
      <div style="border: 1px solid #ccc;border-width: 0 0 0 1px;padding: 0 10px;" class="left-notification">
          <ul class="notification-menu">
              <!--notification info start-->
               <li>
			{ORGS}
               </li>
         <!--notification info end-->
         </ul>
      </div>
      <div style="border: 1px solid #ccc;border-width: 0 1px;padding: 0 10px;" class="left-notification">
         <ul class="notification-menu">
               <!--notification info start-->
               <li>
			<a href="./index.php?module=work&MOD_op=news" class="btn btn-default info-number">
                                <i class="fa fa-bell-o"></i>
                                <span class="badge bg-warning"> {MESSAGES} </span>
                        </a>
               </li>
         <!--notification info end-->
         </ul>
      </div>
      <!--left notification end-->

      <!--right notification start-->
         <div class="right-notification">
                <ul class="notification-menu">
                      <li>
			    <a style="font-size: 16px;color: #1F1F1F;" href="javascript:;"
                               class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					{USERNAME}
                                <span class=" fa fa-angle-down"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-usermenu purple pull-right">
                                <li><a href="./index.php?module=users&norm_user_op=change_profile">个人信息</a></li>
								{CONTROLPANEL}
                                <li>
                                    <a href="./index.php?module=users&norm_user_op=logout"><i
                                            class="fa fa-sign-out pull-right"></i> 退出</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!--right notification end-->
            </div>
        </div>
