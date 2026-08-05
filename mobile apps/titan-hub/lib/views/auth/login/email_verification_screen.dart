import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:pin_code_fields/pin_code_fields.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/controller/auth/login/signin_controller.dart';
import 'package:qrpay/utils/custom_color.dart';
import 'package:qrpay/utils/custom_style.dart';
import 'package:qrpay/widgets/text_labels/custom_title_heading_widget.dart';

import '../../../controller/auth/login/sms_verification_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/responsive_layout.dart';
import '../../../utils/size.dart';
import '../../../widgets/appbar/back_button.dart';
import '../../../widgets/buttons/primary_button.dart';
import '../../../widgets/text_labels/title_heading2_widget.dart';
import '../../../widgets/text_labels/title_heading4_widget.dart';

class EmailVerificationScreen extends StatelessWidget {
  EmailVerificationScreen({super.key});
  final controller = Get.put(SMSVerificationController());
  final _otpFormKey = GlobalKey<FormState>();
  final signInController = Get.put(SignInController());

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
        _timerWidget(context),
        _continueButtonWidget(context),
      ],
    );
  }

  _titleAndSubtitleWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 3,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          TitleHeading2Widget(text: Strings.oTPVerification),
          verticalSpace(Dimensions.heightSize * 0.7),
          TitleHeading4Widget(
            text:
                "${Strings.enterTheOTPCodeSendTo} ${signInController.emailController.text}",
          ),
        ],
      ),
    );
  }

  _inputWidget(BuildContext context) {
    return Form(
      key: _otpFormKey,
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          Padding(
            padding: EdgeInsets.only(
              top: Dimensions.heightSize * 5,
            ),
            child: PinCodeTextField(
              cursorColor: CustomColor.primaryLightColor,
              controller: controller.otpController,
              appContext: context,
              length: 6,
              obscureText: false,
              keyboardType: TextInputType.number,
              textStyle: TextStyle(color: CustomColor.primaryLightColor),
              animationType: AnimationType.fade,
              validator: (v) {
                if (v!.length < 3) {
                  return Strings.pleaseFillOutTheField;
                } else {
                  return null;
                }
              },
              pinTheme: PinTheme(
                  shape: PinCodeFieldShape.box,
                  borderRadius: BorderRadius.circular(Dimensions.radius * 0.7),
                  selectedColor: CustomColor.primaryLightColor,
                  activeColor: CustomColor.primaryLightColor,
                  inactiveColor: CustomColor.blackColor,
                  fieldHeight: 50,
                  fieldWidth: 48,
                  errorBorderColor: CustomColor.redColor,
                  activeFillColor: CustomColor.transparent,
                  borderWidth: 2,
                  fieldOuterPadding: const EdgeInsets.all(1)),
              onChanged: (value) {
                controller.changeCurrentText(value);
              },
            ),
          ),
        ],
      ),
    );
  }

  _timerWidget(BuildContext context) {
    return Obx(
      () => Container(
        margin: EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.access_time_outlined,
              color: CustomColor.primaryLightColor,
            ),
            SizedBox(width: Dimensions.widthSize * 0.4),
            CustomTitleHeadingWidget(
              text: controller.secondsRemaining.value >= 0 &&
                      controller.secondsRemaining.value <= 9
                  ? '00:0${controller.secondsRemaining.value}'
                  : '00:${controller.secondsRemaining.value}',
              style: CustomStyle.darkHeading4TextStyle.copyWith(
                  fontWeight: FontWeight.w600,
                  color: CustomColor.primaryLightColor),
            ),
          ],
        ),
      ),
    );
  }

  _continueButtonWidget(BuildContext context) {
    return Column(
      children: [
        Obx(
          () => signInController.isLoading2
              ? const CustomLoadingAPI()
              : PrimaryButton(
                  title: Strings.submit,
                  onPressed: () {
                    if (_otpFormKey.currentState!.validate()) {
                      signInController.verifyEmailProcess(
                          otpCode: controller.otpController.text);
                    }
                  },
                ),
        ),
        verticalSpace(Dimensions.heightSize * 2),
        Obx(
          () => Visibility(
            visible: controller.enableResend.value,
            child: InkWell(
              onTap: () {
                signInController.sendOTPEmailProcess();
                controller.resendCode();
              },
              child: signInController.isSendOTPLoading
                  ? const CustomLoadingAPI()
                  : CustomTitleHeadingWidget(
                      text: Strings.resendCode,
                      style: CustomStyle.darkHeading4TextStyle.copyWith(
                        fontSize: Dimensions.headingTextSize3,
                        color: CustomColor.primaryLightColor,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
            ),
          ),
        ),
      ],
    );
  }
}
