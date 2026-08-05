import 'package:qrpay/utils/basic_screen_imports.dart';

extension PreviewAmount on Widget {
  Widget previewAmount({
    required amount,
  }) {
    return Container(
      height: 150,
      decoration: BoxDecoration(
        color: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.primaryBGLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      margin:
          EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical * 0.2),
      child: Column(
        mainAxisAlignment: mainCenter,
        children: [
          CustomTitleHeadingWidget(
            text: amount,
            textAlign: TextAlign.center,
            style: CustomStyle.darkHeading1TextStyle.copyWith(
              fontSize: Dimensions.headingTextSize4 * 2,
              fontWeight: FontWeight.w800,
              color: CustomColor.primaryLightColor,
            ),
          ),
          CustomTitleHeadingWidget(
            text: Strings.enteredAmount,
            textAlign: TextAlign.center,
            style: Get.isDarkMode
                ? CustomStyle.darkHeading4TextStyle
                : CustomStyle.lightHeading4TextStyle,
          ),
        ],
      ),
    );
  }
}
