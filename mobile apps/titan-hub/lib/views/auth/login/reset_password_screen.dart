import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/widgets/inputs/password_input_widget.dart';
import 'package:qrpay/widgets/text_labels/title_heading2_widget.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/auth/login/reset_password_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/responsive_layout.dart';
import '../../../utils/size.dart';
import '../../../widgets/appbar/back_button.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/others/congratulation_widget.dart';
import '../../../widgets/text_labels/title_heading4_widget.dart';

class ResetPasswordScreen extends StatelessWidget {
  ResetPasswordScreen({super.key});
  final controller = Get.put(ResetPasswordController());

  final _resetFormKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: PopScope(
        canPop: true,
        onPopInvokedWithResult: (bool didPop, Object? result) {
          if (didPop) {
            Get.offAllNamed(Routes.signInScreen);
          }
        },
        child: Scaffold(
          appBar: AppBar(
            elevation: 0,
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            leading: BackButtonWidget(
              onTap: () {
                Get.toNamed(Routes.signInScreen);
              },
            ),
          ),
          body: _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      physics: const BouncingScrollPhysics(),
      children: [
        _titleAndSubtitleWidget(context),
        _inputWidget(context),
        _continueButtonWidget(context),
      ],
    );
  }

  _titleAndSubtitleWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 0.1,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          TitleHeading2Widget(text: Strings.resetPassword),
          verticalSpace(Dimensions.heightSize * 0.7),
          const TitleHeading4Widget(
            text: Strings.resetPasswordDetails,
          )
        ],
      ),
    );
  }

  _inputWidget(BuildContext context) {
    return Container(
      margin:
          EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical * 1.4),
      child: Form(
        key: _resetFormKey,
        child: Column(
          mainAxisAlignment: mainCenter,
          children: [
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
      ),
    );
  }

  _continueButtonWidget(BuildContext context) {
    return Column(
      children: [
        Obx(() => controller.isLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: Strings.resetPassword,
                onPressed: () {
                  if (_resetFormKey.currentState!.validate()) {
                    controller.resetApiProcess().then((value) {
                      if (context.mounted) {
                        StatusScreen.show(
                            context: context,
                            subTitle: Strings.yourPasswordHasBeen.tr,
                            onPressed: () {
                              Get.offAllNamed(Routes.signInScreen);
                            });
                      }
                    });
                  }
                },
              )),
        verticalSpace(Dimensions.heightSize * 2),
      ],
    );
  }
}
