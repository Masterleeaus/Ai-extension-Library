import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/widgets/text_labels/title_heading5_widget.dart';

class LimitWithExchangeRateWidget extends StatelessWidget {
  const LimitWithExchangeRateWidget(
      {super.key,
      required this.fee,
      required this.limit,
      required this.exchangeRate});
  final String fee;
  final String limit;
  final String exchangeRate;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin:
          EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical * 0.2),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Row(
            mainAxisAlignment: mainCenter,
            children: [
              TitleHeading5Widget(
                text: Strings.exchangeRate,
                textAlign: TextAlign.left,
                fontSize: Dimensions.headingTextSize5,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor
                    : CustomColor.primaryLightColor.withOpacity(0.6),
              ),
              TitleHeading5Widget(
                text: ": $exchangeRate ",
                textAlign: TextAlign.left,
                fontSize: Dimensions.headingTextSize5,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor
                    : CustomColor.primaryLightColor.withOpacity(0.6),
              ),
            ],
          ),
          verticalSpace(Dimensions.heightSize * 0.2),
          Row(
            mainAxisAlignment: mainCenter,
            children: [
              TitleHeading5Widget(
                text: Strings.feesAndCharges,
                textAlign: TextAlign.left,
                fontSize: Dimensions.headingTextSize5,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor
                    : CustomColor.primaryLightColor.withOpacity(0.6),
              ),
              TitleHeading5Widget(
                text: ": $fee ",
                textAlign: TextAlign.left,
                fontSize: Dimensions.headingTextSize5,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor
                    : CustomColor.primaryLightColor.withOpacity(0.6),
              ),
            ],
          ),
          verticalSpace(Dimensions.heightSize * 0.2),
          Row(
            mainAxisAlignment: mainCenter,
            children: [
              TitleHeading5Widget(
                text: Strings.limit,
                textAlign: TextAlign.left,
                fontSize: Dimensions.headingTextSize5,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor
                    : CustomColor.primaryLightColor.withOpacity(0.6),
              ),
              TitleHeading5Widget(
                text: ": $limit ",
                textAlign: TextAlign.left,
                fontSize: Dimensions.headingTextSize5,
                fontWeight: FontWeight.w500,
                color: Get.isDarkMode
                    ? CustomColor.primaryDarkTextColor
                    : CustomColor.primaryLightColor.withOpacity(0.6),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
