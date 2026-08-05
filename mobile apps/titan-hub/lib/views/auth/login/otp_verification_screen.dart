import 'package:pin_code_fields/pin_code_fields.dart';
import 'package:qrpay/controller/auth/login/signin_controller.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/auth/login/otp_reset_controller.dart';
import '../../../routes/routes.dart';
import '../../../utils/responsive_layout.dart';
import '../../../widgets/appbar/back_button.dart';

class ResetOtpScreen extends StatelessWidget {
  ResetOtpScreen({super.key});
  final controller = Get.put(ResetOtpController());
  final _otpFormKey = GlobalKey<FormState>();
  final signInController = Get.put(SignInController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: PopScope(
        canPop: true,
        onPopInvokedWithResult: (bool didPop, Object? result) {
          if (didPop) {
            Get.toNamed(Routes.signInScreen);
            signInController.emailForgotController.clear();
          }
        },
        child: Scaffold(
          appBar: AppBar(
            elevation: 0,
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            leading: BackButtonWidget(
              onTap: () {
                Get.toNamed(Routes.signInScreen);
                signInController.emailForgotController.clear();
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
          Column(
            crossAxisAlignment: crossStart,
            children: [
              const TitleHeading4Widget(
                text: Strings.enterTheOTPCodeSendTo,
              ),
              TitleHeading4Widget(
                text: signInController.emailForgotController.text,
              ),
            ],
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
              errorTextSpace: Dimensions.heightSize * 2,
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
                  return Strings.pleaseFillOutTheField.translation;
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
      () => Column(
        children: [
          Visibility(
              visible: controller.enableResend.value,
              child: SizedBox(
                height: MediaQuery.of(context).size.height * 0.10,
              )),
          Visibility(
            visible: !controller.enableResend.value,
            child: Container(
              margin:
                  EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical),
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
          ),
        ],
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
                      signInController.verifyForgotEmailProcess(
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
                signInController.sendForgotOTPEmailProcess();
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
