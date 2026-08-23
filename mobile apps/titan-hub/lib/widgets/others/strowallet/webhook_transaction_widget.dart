

import '../../../language/language_controller.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../text_labels/title_heading5_widget.dart';

class TransactionWebWidget extends StatelessWidget {
  const TransactionWebWidget({
    super.key,
    required this.amount,
    this.payableAmount = '',
    required this.title,
    this.status,
    required this.transaction,
    this.requestMoney = false,
  });

  final String title, amount, payableAmount, transaction;
  final String? status;
  final bool requestMoney;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: Dimensions.marginSizeVertical * 0.3),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.radius),
          color: Get.isDarkMode
              ? CustomColor.whiteColor.withValues(alpha:0.06)
              : CustomColor.whiteColor,
        ),
        padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
        child: Row(
          children: [
            Expanded(
              flex: 3,
              child: TitleHeading3Widget(
                text: Get.find<LanguageController>().getTranslation(
                  snakeCaseToCamelCase(title),
                ),
                maxLines: 2,
                fontSize: Dimensions.headingTextSize4,
              ),
            ),
            Expanded(
              flex: 1,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Visibility(
                    visible: !requestMoney && status != null,
                    child: Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingSize * .5,
                        vertical: Dimensions.paddingSize * .1,
                      ),
                      decoration: BoxDecoration(
                        color: status == 'Pending'
                            ? CustomColor.yellowColor.withValues(alpha:0.8)
                            : status == 'success'
                                ? CustomColor.greenColor.withValues(alpha:0.8)
                                : CustomColor.redColor.withValues(alpha:0.8),
                        borderRadius: BorderRadius.circular(Dimensions.radius),
                      ),
                      child: TitleHeading5Widget(
                        text: status ?? "",
                        fontSize: Dimensions.headingTextSize5,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  Visibility(
                    visible: requestMoney && status != null,
                    child: Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingSize * .5,
                        vertical: Dimensions.paddingSize * .1,
                      ),
                      decoration: BoxDecoration(
                        color: status == '2' || status == '0'
                            ? CustomColor.yellowColor.withValues(alpha:0.8)
                            : status == '1'
                                ? CustomColor.greenColor.withValues(alpha:0.8)
                                : CustomColor.redColor.withValues(alpha:0.8),
                        borderRadius: BorderRadius.circular(Dimensions.radius),
                      ),
                      child: TitleHeading5Widget(
                        text: status == '4'
                            ? 'Rejected'
                            : status == '2'
                                ? 'Pending'
                                : status == '1'
                                    ? 'Success'
                                    : 'Default',
                        fontSize: Dimensions.headingTextSize5,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  TitleHeading3Widget(
                    text: amount,
                    fontSize: Dimensions.headingTextSize5,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String snakeCaseToCamelCase(String input) {
    List<String> parts = input.split('-');
    String camelCase = parts.first.toLowerCase() +
        parts
            .sublist(1)
            .map((part) =>
                part[0].toUpperCase() + part.substring(1).toLowerCase())
            .join();
    return camelCase;
  }
}
