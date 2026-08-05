// ignore_for_file: unrelated_type_equality_checks

import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';
import 'package:qrpay/custom_assets/assets.gen.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/utils/custom_color.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/views/others/custom_image_widget.dart';

import '../../backend/local_storage/local_storage.dart';
import '../app_settings/app_settings_controller.dart';

class OnBoardController extends GetxController {
  var selectedIndex = (0).obs;
  var pageController = PageController();
  final controller = Get.find<AppSettingsController>();

  bool get isLastPage =>
      selectedIndex.value ==
      controller.appSettingsModel.data.appSettings.user.onboardScreen.length -
          1;

  bool get isFirstPage => selectedIndex.value == 0;

  // bool get isSecondPage => selectedIndex.value == 1;

  void nextPage() {
    if (isLastPage) {
    } else {
      pageController.nextPage(
        duration: 300.milliseconds,
        curve: Curves.ease,
      );
    }
  }

  void backPage() {
    pageController.previousPage(
      duration: 300.milliseconds,
      curve: Curves.ease,
    );
  }

  pageNavigate() {
    LocalStorages.saveOnboardDoneOrNot(isOnBoardDone: true);
    Get.offAllNamed(Routes.signInScreen);
  }

  AnimatedContainer buildDot({int? index}) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      margin: EdgeInsets.symmetric(horizontal: 5.w),
      height: 10,
      width: 10,
      decoration: BoxDecoration(
        shape: BoxShape.rectangle,
        color: index == selectedIndex.value
            ? CustomColor.primaryLightColor
            : CustomColor.primaryTextColor.withOpacity(0.3),
      ),
    );
  }

  AnimatedContainer buildArrow({int? index}) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      margin: EdgeInsets.symmetric(horizontal: 2.w),
      child: CustomImageWidget(
        height: 11.42,
        width: 7.17,
        color: index == selectedIndex.value
            ? CustomColor.blackColor
            : CustomColor.primaryTextColor.withOpacity(0.5),
        path: Assets.icon.rightArrow,
      ),
    );
  }

  dotWidget() {
    return Container(
      margin: EdgeInsets.only(
        left: Dimensions.widthSize * 1.7,
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.start,
        children: List.generate(
          controller
              .appSettingsModel.data.appSettings.user.onboardScreen.length,
          (index) => buildDot(index: index),
        ),
      ),
    );
  }

  arrowWidget() {
    return InkWell(
      onTap: () {
        isFirstPage || isLastPage ? nextPage() : pageNavigate();
      },
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: List.generate(
          controller
              .appSettingsModel.data.appSettings.user.onboardScreen.length,
          (index) => buildArrow(index: index),
        ),
      ),
    );
  }
}
