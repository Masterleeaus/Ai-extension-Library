import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';
import 'package:qrpay/widgets/inputs/password_input_widget.dart';

import '../../controller/drawer/change_password_controller.dart';
import '../../language/english.dart';

class ChangePasswordScreen extends StatelessWidget {
  ChangePasswordScreen({super.key});

  final controller = Get.put(PasswordController());
  final passwordKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.changePassword),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.9),
      physics: const BouncingScrollPhysics(),
      children: [
        _inputWidget(context),
        _buttonWidget(context),
      ],
    );
  }

  _inputWidget(BuildContext context) {
    return Form(
      key: passwordKey,
      child: Column(
        children: [
          verticalSpace(Dimensions.heightSize * 2),
          PasswordInputWidget(
            controller: controller.oldPasswordController,
            hint: Strings.enterOldPassword,
            label: Strings.oldPassword,
          ),
          verticalSpace(Dimensions.heightSize),
          PasswordInputWidget(
            controller: controller.newPasswordController,
            hint: Strings.enterNewPassword,
            label: Strings.newPassword,
          ),
          verticalSpace(Dimensions.heightSize),
          PasswordInputWidget(
            controller: controller.confirmPasswordController,
            hint: Strings.enterConfirmPassword,
            label: Strings.confirmPassword,
          ),
        ],
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.marginSizeVertical * 2),
      child: Obx(
        () => controller.isLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                onPressed: () {
                  // controller.gotoNavigation();
                  if (passwordKey.currentState!.validate()) {
                    controller.updatePasswordProcess();
                  }
                },
                title: Strings.changePassword,
              ),
      ),
    );
  }
}
