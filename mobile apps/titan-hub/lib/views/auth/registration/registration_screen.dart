import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/language/language_controller.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/buttons/custom_text_button.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';
import 'package:qrpay/widgets/text_labels/title_heading2_widget.dart';
import 'package:qrpay/widgets/text_labels/title_heading4_widget.dart';

import '../../../controller/auth/registration/registration_controller.dart';
import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/inputs/primary_input_filed.dart';
import '../../../widgets/logo/basic_logo_widget.dart';

final phoneNumberFormKey = GlobalKey<FormState>();

class RegistrationScreen extends StatelessWidget {
  RegistrationScreen({super.key});

  final _signUpFormKey = GlobalKey<FormState>();
  final controller = Get.put(RegistrationController());
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
    final width = MediaQuery.of(context).size.width;
    return SingleChildScrollView(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * 0.05,
      ),
      physics: const BouncingScrollPhysics(),
      child: SizedBox(
        height: height,
        width: width,
        child: ListView(
          physics: const BouncingScrollPhysics(),
          children: [
            _logoWidget(
              context,
              logoHeight: height * 0.27,
            ),
            _bottomContainerWidget(
              context,
              height: height * 0.8,
              child: Column(
                children: [
                  _titleAndSubtitleWidget(context),
                  _inputAndForgotWidget(context),
                  _buttonWidget(context),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  _logoWidget(BuildContext context, {required double logoHeight}) {
    return Container(
      height: logoHeight,
      margin: EdgeInsets.only(top: Dimensions.marginSizeVertical),
      padding: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 3,
        bottom: Dimensions.marginSizeVertical * 1.5,
      ),
      child: BasicLogoWidget(),
    );
  }

  _bottomContainerWidget(BuildContext context,
      {required Widget child, required double height}) {
    Radius borderRadius = const Radius.circular(20);
    return Container(
      height: height,
      margin: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.55),
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
            offset: const Offset(0, 0), // changes position of shadow
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
          TitleHeading2Widget(text: Strings.signUpInformation.tr),
          verticalSpace(
            Dimensions.heightSize * 0.5,
          ),
          TitleHeading4Widget(
            text: Strings.signUpDetails.tr,
          ),
        ],
      ),
    );
  }

  _inputAndForgotWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 0.8,
      ),
      child: Column(
        crossAxisAlignment: crossEnd,
        mainAxisSize: mainMin,
        children: [
          Form(
            key: _signUpFormKey,
            child: PrimaryInputWidget(
              controller: controller.emailController,
              hint: Strings.enterEmailAddress.tr,
              label: Strings.emailAddress.tr,
            ),
          ),
        ],
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        bottom: Dimensions.marginSizeVertical,
        top: Dimensions.marginSizeVertical,
      ),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          Obx(
            () => controller.isLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.signUp.tr,
                    onPressed: () {
                      if (_signUpFormKey.currentState!.validate()) {
                        controller.checkExistUserProcess();
                      }
                    },
                    buttonTextColor: CustomColor.whiteColor,
                  ),
          ),
          verticalSpace(Dimensions.heightSize * 2.5),
          RichText(
            text: TextSpan(
              text: languageController
                  .getTranslation(Strings.alreadyHaveAnAccount),
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
                        controller.onPressedSignIn();
                      },
                      text:
                          languageController.getTranslation(Strings.richSignIn),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
