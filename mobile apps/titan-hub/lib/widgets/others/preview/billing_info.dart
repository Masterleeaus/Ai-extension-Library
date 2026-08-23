import 'package:qrpay/utils/basic_screen_imports.dart';

extension PreviewRecipient on Widget {
  Widget billingInfo({
    required recipient,
    required billType,
    required billNumber,
    required billMonth,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: CustomColor.whiteColor,
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
              text: recipient,
              textAlign: TextAlign.left,
              style: CustomStyle.f20w600pri,
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
                      text: Strings.billType,
                      fontWeight: FontWeight.w400,
                      color: CustomColor.primaryLightColor.withOpacity(0.4),
                    ),
                    TitleHeading3Widget(
                      text: billType,
                      fontWeight: FontWeight.w600,
                      color: CustomColor.primaryLightColor.withOpacity(0.6),
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.4),
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.billNumber,
                      fontWeight: FontWeight.w400,
                      color: CustomColor.primaryLightColor.withOpacity(0.4),
                    ),
                    TitleHeading3Widget(
                      text: billNumber,
                      fontWeight: FontWeight.w600,
                      color: CustomColor.primaryLightColor.withOpacity(0.6),
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.4),
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    TitleHeading4Widget(
                      text: Strings.billMonths,
                      fontWeight: FontWeight.w400,
                      color: CustomColor.primaryLightColor.withOpacity(0.4),
                    ),
                    TitleHeading3Widget(
                      text: billMonth,
                      fontWeight: FontWeight.w600,
                      color: CustomColor.primaryLightColor.withOpacity(0.6),
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
