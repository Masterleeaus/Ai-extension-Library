import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/widgets/others/glass_widget.dart';

import '../../../backend/local_storage/local_storage.dart';
import '../../../backend/utils/custom_loading_api.dart';
import '../../../controller/auth/login/signin_controller.dart';
import '../../../controller/fingerprint/fingerprint_controller.dart';
import '../../../language/language_controller.dart';
import '../../../routes/routes.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../utils/responsive_layout.dart';
import '../../../widgets/buttons/custom_text_button.dart';
import '../../../widgets/inputs/password_input_widget.dart';
import '../../../widgets/logo/basic_logo_widget.dart';

class SignInScreen extends StatefulWidget {
  const SignInScreen({super.key});

  @override
  State<SignInScreen> createState() => _SignInScreenState();
}

class _SignInScreenState extends State<SignInScreen> {
  final controller = Get.put(SignInController());

  final phoneNumberFormKey = GlobalKey<FormState>();

  final signInFormKey = GlobalKey<FormState>();

  final forgotPasswordFormKey = GlobalKey<FormState>();

  final fingerprintController = Get.put(FingerprintController());
  final languageController = Get.put(LanguageController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: AppBar(
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          surfaceTintColor: Colors.transparent,
          scrolledUnderElevation: 0,
          elevation: 0,
          automaticallyImplyLeading: false,
        ),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    final height = MediaQuery.of(context).size.height;
    return SingleChildScrollView(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * 0.05,
      ),
      child: Column(
        children: [
          _logoWidget(
            context,
            logoHeight: height * 0.27,
          ),
          _bottomContainerWidget(
            context,
            child: Column(
              children: [
                _titleAndSubtitleWidget(context),
                _inputAndForgotWidget(context),
                _buttonWidget(context),
                verticalSpace(Dimensions.heightSize * 4.5)
              ],
            ),
          ),
        ],
      ),
    );
  }

  _logoWidget(BuildContext context, {required double logoHeight}) {
    return Container(
      // height: logoHeight,
      padding: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 2,
        bottom: Dimensions.marginSizeVertical * 1.5,
      ),
      child: BasicLogoWidget(),
    );
  }

  _bottomContainerWidget(
    BuildContext context, {
    required Widget child,
  }) {
    Radius borderRadius = const Radius.circular(20);
    return Container(
      margin: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.5),
      decoration: BoxDecoration(
        color: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.primaryBGLightColor,
        borderRadius:
            BorderRadius.only(topLeft: borderRadius, topRight: borderRadius),
        boxShadow: [
          BoxShadow(
            color: CustomColor.primaryLightColor.withOpacity(0.015),
            spreadRadius: 7,
            blurRadius: 5,
            offset: const Offset(0, 0),
          ),
        ],
      ),
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: child,
    );
  }

  _titleAndSubtitleWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical * 0.5,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          TitleHeading2Widget(
            text: Strings.signInInformation.tr,
          ),
          verticalSpace(Dimensions.heightSize * 0.5),
          TitleHeading4Widget(
            text: Strings.signInInformationSubtitle.tr,
          ),
        ],
      ),
    );
  }

  _inputAndForgotWidget(BuildContext context) {
    return Form(
      key: signInFormKey,
      child: Column(
        crossAxisAlignment: crossEnd,
        children: [
          verticalSpace(Dimensions.heightSize),
          PrimaryInputWidget(
            controller: controller.emailController,
            hint: Strings.enterEmailAddress.tr,
            label: Strings.emailAddress.tr,
          ),
          verticalSpace(Dimensions.heightSize * 0.7),
          PasswordInputWidget(
            controller: controller.passwordController,
            hint: Strings.password.tr,
            label: Strings.password.tr,
          ),
          verticalSpace(
            Dimensions.heightSize * 0.3,
          ),
          InkWell(
            onTap: () => _openDialogue(context),
            child: TitleHeading4Widget(
              fontWeight: FontWeight.w600,
              fontSize: Dimensions.headingTextSize5,
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor
                  : CustomColor.primaryTextColor,
              text: Strings.forgotPassword.tr,
            ),
          ),
        ],
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical,
        bottom: Dimensions.marginSizeVertical,
      ),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          Obx(
            () => controller.isLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.signIn.tr,
                    onPressed: () {
                      if (signInFormKey.currentState!.validate()) {
                        controller.loginProcess();
                      }
                    },
                    buttonTextColor: CustomColor.whiteColor,
                  ),
          ),
          verticalSpace(Dimensions.heightSize),
          Visibility(
            visible:
                fingerprintController.supportState == SupportState.supported &&
                    LocalStorages.isLoggedIn(),
            child: PrimaryButton(
              title: Strings.signInWithTouchId.tr,
              onPressed: () async {
                bool isAuthenticated =
                    await Authentication.authenticateWithBiometrics();

                if (isAuthenticated) {
                  Get.offAndToNamed(Routes.bottomNavBarScreen);
                } else {
                  debugPrint('isAuthenticated : false');
                }
              },
              buttonTextColor: CustomColor.whiteColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 1.4),
          Obx(
            () => RichText(
              text: TextSpan(
                text: languageController.getTranslation(Strings.haveAccount),
                style: GoogleFonts.inter(
                  fontSize: Dimensions.headingTextSize5,
                  color: Get.isDarkMode
                      ? CustomColor.primaryDarkTextColor.withOpacity(0.8)
                      : CustomColor.primaryTextColor.withOpacity(0.8),
                  fontWeight: FontWeight.w500,
                ),
                children: [
                  WidgetSpan(
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.widthSize * 0.3,
                      ),
                      child: CustomTextButton(
                        onPressed: () {
                          controller.onPressedSignUP();
                        },
                        text: languageController
                            .getTranslation(Strings.richSignUp),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  _openDialogue(
    BuildContext context,
  ) {
    return showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: Colors.transparent,
        alignment: Alignment.center,
        insetPadding: EdgeInsets.all(Dimensions.paddingSize * 0.3),
        contentPadding: EdgeInsets.zero,
        clipBehavior: Clip.antiAliasWithSaveLayer,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        content: Builder(
          builder: (context) {
            var width = MediaQuery.of(context).size.width;
            return Stack(
              children: [
                Container(
                  width: width * 0.84,
                  margin: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                  padding: EdgeInsets.all(Dimensions.paddingSize * 0.9),
                  decoration: BoxDecoration(
                    color: Get.isDarkMode
                        ? CustomColor.primaryBGDarkColor
                        : CustomColor.whiteColor,
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 1.4),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: crossStart,
                    children: [
                      SizedBox(height: Dimensions.heightSize * 2),
                      TitleHeading2Widget(text: Strings.forgotPassword.tr),
                      verticalSpace(Dimensions.heightSize * 0.5),
                      TitleHeading4Widget(
                          text: Strings.pleaseEnterYourRegister.tr),
                      SizedBox(height: Dimensions.heightSize * 1),
                      Form(
                        key: forgotPasswordFormKey,
                        child: PrimaryInputWidget(
                          controller: controller.emailForgotController,
                          hint: Strings.enterEmailAddress.tr,
                          label: Strings.emailAddress.tr,
                        ),
                      ),
                      verticalSpace(Dimensions.heightSize * 0.5),
                      Obx(
                        () => controller.isSendForgotOTPLoading
                            ? const CustomLoadingAPI()
                            : PrimaryButton(
                                title: Strings.forgotPassword.tr,
                                onPressed: () {
                                  if (forgotPasswordFormKey.currentState!
                                      .validate()) {
                                    controller.goToForgotEmailVerification();
                                  }
                                },
                                borderColor: CustomColor.primaryLightColor,
                              ),
                      ),
                    ],
                  ),
                ),
                Positioned(
                  top: 0,
                  right: 0,
                  child: GestureDetector(
                      onTap: () {
                        Get.back();
                      },
                      child: CircleAvatar(
                        backgroundColor: CustomColor.primaryLightColor,
                        child: const Icon(
                          Icons.close,
                          color: CustomColor.whiteColor,
                        ),
                      )),
                ),
              ],
            );
          },
        ),
      ).customGlassWidget(
        blurY: 1,
        blurX: 1,
      ),
    );
  }
}
