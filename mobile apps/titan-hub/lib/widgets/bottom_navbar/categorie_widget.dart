import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/views/others/custom_image_widget.dart';

class CategoriesWidget extends StatelessWidget {
  const CategoriesWidget({
    super.key,
    required this.icon,
    required this.text,
    required this.onTap,
  });

  final String icon, text;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Column(
        children: [
          CircleAvatar(
            radius: 27,
            backgroundColor: Get.isDarkMode
                ? CustomColor.whiteColor.withOpacity(0.06)
                : CustomColor.primaryLightColor.withOpacity(0.06),
            child: CustomImageWidget(
              path: icon,
              height: 24,
              width: 24,
              color: Get.isDarkMode
                  ? CustomColor.whiteColor
                  : CustomColor.blackColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 0.5),
          CustomTitleHeadingWidget(
            text: text,
            maxLines: 2,
            textAlign: TextAlign.center,
            style: Get.isDarkMode
                ? CustomStyle.darkHeading5TextStyle.copyWith(
                    fontWeight: FontWeight.w600,
                    fontSize: Dimensions.headingTextSize5 * 0.8,
                  )
                : CustomStyle.lightHeading5TextStyle.copyWith(
                    fontWeight: FontWeight.w600,
                    fontSize: Dimensions.headingTextSize5 * 0.8,
                  ),
          ),
        ],
      ),
    );
  }
}
