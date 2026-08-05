import 'package:qrpay/utils/basic_screen_imports.dart';

extension AmountInformation2 on Widget {
  Widget billingInformationWidget({
    required enterAmount,
    required exchangeRate,
    required conversionAmount,
    required totalCharge,
    required totalPayable,
  }) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.heightSize * 0.4),
      decoration: BoxDecoration(
        color: Get.isDarkMode
            ? CustomColor.primaryBGDarkColor
            : CustomColor.primaryBGLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Padding(
            padding: EdgeInsets.only(
              top: Dimensions.marginSizeVertical * 0.7,
              bottom: Dimensions.marginSizeVertical * 0.3,
              left: Dimensions.paddingSize * 0.7,
              right: Dimensions.paddingSize * 0.7,
            ),
            child: CustomTitleHeadingWidget(
              text: Strings.amountInformation,
              textAlign: TextAlign.left,
              style: Get.isDarkMode
                  ? CustomStyle.f20w600pri.copyWith(
                      color: CustomColor.primaryDarkTextColor,
                    )
                  : CustomStyle.f20w600pri,
            ),
          ),
          Divider(
            thickness: 1,
            color: CustomColor.primaryLightColor.withOpacity(0.2),
          ),
          Padding(
            padding: EdgeInsets.only(
              top: Dimensions.marginSizeVertical * 0.3,
              bottom: Dimensions.marginSizeVertical * 0.6,
              left: Dimensions.paddingSize * 0.7,
              right: Dimensions.paddingSize * 0.7,
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.amount,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.4,
                            ),
                    ),
                    TitleHeading3Widget(
                      text: enterAmount,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.6,
                            ),
                      fontWeight: FontWeight.w600,
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.7),
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.exchangeRate,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.4,
                            ),
                    ),
                    TitleHeading3Widget(
                      text: exchangeRate,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.6,
                            ),
                      fontWeight: FontWeight.w600,
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.7),
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.conversionAmount,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.4,
                            ),
                    ),
                    TitleHeading3Widget(
                      text: conversionAmount,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.6,
                            ),
                      fontWeight: FontWeight.w600,
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.7),
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.totalCharge,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.4,
                            ),
                    ),
                    TitleHeading3Widget(
                      text: totalCharge,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.6,
                            ),
                      fontWeight: FontWeight.w600,
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.7),
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.totalPayable,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.4,
                            ),
                    ),
                    TitleHeading3Widget(
                      text: totalPayable,
                      color: Get.isDarkMode
                          ? CustomColor.primaryDarkTextColor.withOpacity(0.6)
                          : CustomColor.primaryLightColor.withOpacity(
                              0.6,
                            ),
                      fontWeight: FontWeight.w600,
                    ),
                  ],
                ),
              ],
            ),
          )
        ],
      ),
    );
  }
}
