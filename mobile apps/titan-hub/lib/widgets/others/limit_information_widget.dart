import 'package:shimmer/shimmer.dart';
import '../../controller/categories/remaining_balance_controller/ramaining_controller.dart';
import '../../utils/basic_screen_imports.dart';
import '../text_labels/title_heading5_widget.dart';

class LimitInformationWidget extends StatelessWidget {
  LimitInformationWidget({
    super.key,
    required this.transactionLimit,
    required this.dailyLimit,
    required this.remainingDailyLimit,
    required this.monthlyLimit,
    required this.remainingMonthLimit,
    this.showDailyLimit = true, // Controls daily limit visibility
    this.showMonthlyLimit = true, // Controls monthly limit visibility
  });

  final String transactionLimit;
  final String dailyLimit;
  final String remainingDailyLimit;
  final String monthlyLimit;
  final String remainingMonthLimit;
  final bool showDailyLimit;
  final bool showMonthlyLimit;
  final controller = Get.put(RemaingBalanceController());

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(Dimensions.paddingSize * 0.4),
      margin:
          EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical * 0.4),
      decoration: BoxDecoration(
        color: CustomColor.primaryBGLightColor,
        borderRadius: BorderRadius.circular(Dimensions.radius),
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          TitleHeading3Widget(
              text: Strings.limitInformation,
              color: CustomColor.primaryLightColor),
          verticalSpace(Dimensions.heightSize * 0.6),

          // Daily Limit section (only if showDailyLimit is true and dailyLimit is not "0.00")
          if (showDailyLimit)
            Column(
              children: [
                Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: crossStart,
                        children: [
                          TitleHeading5Widget(
                              text: Strings.dailyLimit,
                              color: CustomColor.primaryLightColor),
                          TitleHeading3Widget(
                            text: dailyLimit,
                            fontSize: Dimensions.headingTextSize4 - 1,
                            color:
                                CustomColor.primaryLightColor.withValues(alpha:0.5),
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: Column( 
                        crossAxisAlignment: crossEnd,
                        children: [
                          TitleHeading5Widget(
                              text: Strings.remainingDailyLimit,
                              color: CustomColor.primaryLightColor),
                          Obx(
                            () => controller.isLoading
                                ? Shimmer.fromColors(
                                    baseColor: Colors.grey[300]!,
                                    highlightColor: Colors.grey[100]!,
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Container(
                                          width: double.infinity,
                                          height: 16.0,
                                          color: Colors.white,
                                        ),
                                      ],
                                    ),
                                  )
                                : TitleHeading3Widget(
                                    fontSize: Dimensions.headingTextSize4 - 1,
                                    text: remainingDailyLimit,
                                    color: CustomColor.primaryLightColor
                                        .withValues(alpha:0.5),
                                  ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.7),
              ],
            ),

          // Monthly Limit section (only if showMonthlyLimit is true and monthlyLimit is not "0.00")
          if (showMonthlyLimit)
            Row(
              mainAxisAlignment: mainSpaceBet,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: crossStart,
                    children: [
                      TitleHeading5Widget(
                          text: Strings.monthlyLimit,
                          color: CustomColor.primaryLightColor),
                      TitleHeading3Widget(
                        fontSize: Dimensions.headingTextSize4 - 1,
                        text: monthlyLimit,
                        color: CustomColor.primaryLightColor.withValues(alpha:0.5),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: crossEnd,
                    children: [
                      TitleHeading5Widget(
                          text: Strings.remainingMonthlyLimit,
                          fontSize: Dimensions.headingTextSize5 - 1,
                          color: CustomColor.primaryLightColor),
                      Obx(
                        () => controller.isLoading
                            ? Shimmer.fromColors(
                                baseColor: Colors.grey[300]!,
                                highlightColor: Colors.grey[100]!,
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      width: double.infinity,
                                      height: Dimensions.heightSize * 1.4,
                                      color: Colors.white,
                                    ),
                                  ],
                                ),
                              )
                            : TitleHeading3Widget(
                                fontSize: Dimensions.headingTextSize4 - 1,
                                text: remainingMonthLimit,
                                color: CustomColor.primaryLightColor
                                    .withValues(alpha:0.5),
                              ),
                      ),
                    ],
                  ),
                ),
              ],
            ),

          verticalSpace(Dimensions.heightSize * 0.6),

          // Transaction Limit section
          Column(
            crossAxisAlignment: crossStart,
            children: [
              TitleHeading5Widget(
                  text: Strings.transactionLimit,
                  color: CustomColor.primaryLightColor),
              TitleHeading3Widget(
                fontSize: Dimensions.headingTextSize4 - 1,
                text: transactionLimit,
                color: CustomColor.primaryLightColor.withValues(alpha:0.5),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
