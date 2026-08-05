import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../custom_assets/assets.gen.dart';
import '../../language/english.dart';
import '../../language/language_controller.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../views/others/custom_image_widget.dart';
import '../buttons/primary_button.dart';
import '../text_labels/title_subtitle_widget.dart';

class StatusScreen {
  static show({
    required BuildContext context,
    required String subTitle,
    required VoidCallback onPressed,
    bool ifSuccess = true,
    bool showAppName = true,
  }) {
    var widget = PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, Object? result) {
        if (didPop) {}
      },
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: Center(
          child: Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(Dimensions.radius),
            ),
            child: Padding(
              padding: EdgeInsets.symmetric(
                horizontal: Dimensions.marginSizeHorizontal,
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  CustomImageWidget(
                    path: ifSuccess
                        ? Assets.clipart.confirmation
                        : Assets.clipart.confirmation,
                    height: Dimensions.iconSizeLarge * 6,
                    width: Dimensions.iconSizeLarge * 6,
                  ),
                  verticalSpace(18),
                  TitleSubTitleWidget(
                    title: ifSuccess
                        ? Strings.congratulations.tr
                        : Strings.opps.tr,
                    subtitle: showAppName
                        ? '${Get.find<LanguageController>().getTranslation(subTitle)} ${Strings.appName}'
                        : subTitle,
                    crossAxisAlignment: crossCenter,
                  ),
                  verticalSpace(32),
                  PrimaryButton(
                    title: Strings.okay.tr,
                    onPressed: onPressed,
                    borderColor: CustomColor.primaryLightColor,
                    buttonColor: CustomColor.primaryLightColor,
                  )
                ],
              ),
            ),
          ),
        ),
      ),
    );

    showDialog(
      context: context,
      builder: (context) {
        return widget;
      },
    );
  }
}
