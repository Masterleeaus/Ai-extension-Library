import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/custom_assets/assets.gen.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';

import '../../../controller/categories/request_money/request_money_controller.dart';
import '../../../widgets/inputs/request_money_input.dart';
import '../../../widgets/inputs/request_money_input_qr_code.dart';
import '../../../widgets/others/limit_information_widget.dart';
import '../../../widgets/others/limit_widget.dart';
import '../../../widgets/text_labels/title_heading5_widget.dart';

class RequestMoneyScreen extends StatelessWidget {
  RequestMoneyScreen({super.key});

  final controller = Get.put(RequestMoneyController());
  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.requestMoney),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),      
        ),
      ),
    );
  }        

  _bodyWidget(BuildContext context) {
    return ListView(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.9),
      physics: const BouncingScrollPhysics(),
      children: [
        _inputWidget(context),
        Obx(() {
          return LimitWidget(
              fee:
                  '${controller.totalFee.value.toStringAsFixed(4)} ${controller.baseCurrency.value}',
              limit:
                  '${controller.limitMin} - ${controller.limitMax} ${controller.baseCurrency.value}');
        }),
        _limitInformation(context),
        verticalSpace(Dimensions.heightSize * 0.5),
        PrimaryInputWidget(
          controller: controller.remarkController,
          hint: Strings.explainTrx,
          label: Strings.remark,
          isValidator: false,
          maxLines: 5,
        ),
        _buttonWidget(context),
      ],
    );
  }

  _inputWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.marginSizeVertical * 1.6),
      child: Form(
        key: formKey,
        child: Column(
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                CopyRequestMoneyInputWidget(
                  suffixIcon: Assets.icon.scan,
                  suffixColor: CustomColor.whiteColor,
                  onTap: () {
                    Get.toNamed(Routes.requestMoneyQRCodeScreen);
                  },
                  controller: controller.copyInputController,
                  hint: Strings.enterEmailAddress,
                  label: Strings.emailAddress,
                ),
                Obx(() {
                  return TitleHeading5Widget(
                    text: controller.checkUserMessage.value,
                    color: controller.isValidUser.value
                        ? CustomColor.greenColor
                        : CustomColor.redColor,
                  );
                })
              ],
            ),
            verticalSpace(Dimensions.heightSize),
            RequestMoneyInputWithDropdown(
              controller: controller.amountController,
              hint: Strings.zero00,
              label: Strings.amount,
            ),
          ],
        ),
      ),
    );
  }

  _limitInformation(BuildContext context) {
    return LimitInformationWidget(
      showDailyLimit: controller.dailyLimit.value == 0.0 ? false : true,
      showMonthlyLimit: controller.monthlyLimit.value == 0.0 ? false : true,
      transactionLimit:
          '${controller.limitMin.value} - ${controller.limitMax.value} ${controller.baseCurrency.value}',
      dailyLimit:
          '${controller.dailyLimit.value} ${controller.baseCurrency.value}',
      monthlyLimit:
          '${controller.monthlyLimit.value} ${controller.baseCurrency.value}',
      remainingMonthLimit:
          '${controller.remainingController.remainingMonthLyLimit.value} ${controller.baseCurrency.value}',
      remainingDailyLimit:
          '${controller.remainingController.remainingDailyLimit.value} ${controller.baseCurrency.value}',
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Dimensions.marginSizeVertical * 1,
        bottom: Dimensions.marginSizeVertical,
      ),
      child: Obx(
        () => controller.isRequestMoneyLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                buttonColor: controller.isValidUser.value &&
                        double.parse(controller
                                .remainingController.senderAmount.value) >
                            0 &&
                        double.parse(controller
                                .remainingController.senderAmount.value) <=
                            controller.dailyLimit.value &&
                        double.parse(controller
                                .remainingController.senderAmount.value) <=
                            controller.monthlyLimit.value
                    ? CustomColor.primaryLightColor
                    : CustomColor.primaryLightColor.withValues(alpha: 0.3),
                title: Strings.send,
                onPressed: () {
                  if (controller.isValidUser.value &&
                      double.parse(controller
                              .remainingController.senderAmount.value) >
                          0 &&
                      double.parse(controller
                              .remainingController.senderAmount.value) <=
                          controller.dailyLimit.value &&
                      double.parse(controller
                              .remainingController.senderAmount.value) <=
                          controller.monthlyLimit.value) {
                    Get.toNamed(Routes.requestMoneyPreviewScreen);
                  }
                },
              ),
      ),
    );
  }
}
