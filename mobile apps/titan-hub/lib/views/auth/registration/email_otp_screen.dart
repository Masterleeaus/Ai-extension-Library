import 'package:pin_code_fields/pin_code_fields.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/backend/utils/custom_snackbar.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../../controller/auth/registration/otp_email_controoler.dart';
import '../../../controller/auth/registration/registration_controller.dart';
import '../../../utils/responsive_layout.dart';
import '../../../widgets/appbar/back_button.dart';

class EmailOtpScreen extends StatelessWidget {
  EmailOtpScreen({super.key});
  final controller = Get.put(EmailOtpController());
  final emailOtpFormKey = GlobalKey<FormState>();
  final signupController = Get.put(RegistrationController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: AppBar(
          elevation: 0,
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          leading: const BackButtonWidget(),
        ),
        body: _bodyWidget(context),
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
                text: signupController.emailController.text,
              ),
            ],
          )
        ],
      ),
    );
  }

  _inputWidget(BuildContext context) {
    return Form(
      key: emailOtpFormKey,
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
              controller: controller.emailOtpInputController,
              appContext: context,
              length: 6,
              obscureText: false,
              keyboardType: TextInputType.number,
              textStyle: TextStyle(color: CustomColor.primaryLightColor),
              animationType: AnimationType.fade,
              validator: (v) {
                if (v!.length < 6) {
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
          () => signupController.isLoading2
              ? const CustomLoadingAPI()
              : PrimaryButton(
                  title: Strings.verifyOTP,
                  onPressed: () {
                    if (emailOtpFormKey.currentState!.validate()) {
                      signupController.verifyEmailProcess(
                          otpCode: controller.emailOtpInputController.text);
                    } else {
                      CustomSnackBar.error(Strings.theCodeisNotValid.tr);
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
                signupController.sendOTPEmailProcess(
                    email: signupController.emailController.text);
                controller.resendCode();
              },
              child: CustomTitleHeadingWidget(
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
