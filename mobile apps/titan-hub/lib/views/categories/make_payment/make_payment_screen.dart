import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/custom_assets/assets.gen.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';

import '../../../controller/categories/make_payment/make_payment_controller.dart';
import '../../../controller/navbar/dashboard_controller.dart';
import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../widgets/others/congratulation_widget.dart';
import '../../../widgets/others/limit_information_widget.dart';
import '../../../widgets/others/limit_widget.dart';
import '../../../widgets/text_labels/title_heading5_widget.dart';
import 'copy_with_input_make_payment.dart';
import 'input_with_dropdown_make_payment.dart';

class MakePaymentScreen extends StatelessWidget {
  MakePaymentScreen({super.key});

  final controller = Get.put(MakePaymentController());
  final dashboardController = Get.find<DashBoardController>();
  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.makePayment),
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
                MakePaymentCopyInputWidget(
                  suffixIcon: Assets.icon.scan,
                  suffixColor: CustomColor.primaryLightColor,
                  onTap: () {
                    Get.toNamed(Routes.makePaymentQRCodeScreen);
                  },
                  controller: controller.copyInputController,
                  hint: Strings.enterEmailAddress,
                  label: Strings.emailAddress,
                ),
                Obx(() {
                  return TitleHeading5Widget(
                    text: controller.checkUserMessage.value,
                    color: controller.isValidUser.value
                        ? Colors.green
                        : Colors.red,
                  );
                })
              ],
            ),
            verticalSpace(Dimensions.heightSize),
            MakePaymentInputWithDropdown(
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
        top: Dimensions.marginSizeVertical * 2,
        bottom: Dimensions.marginSizeVertical,
      ),
      child: Obx(
        () => controller.isSendMoneyLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                buttonColor: controller.isValidUser.value &&
                        double.parse(controller
                                .remainingController.senderAmount.value) <=
                            controller.dailyLimit.value &&
                        double.parse(controller
                                .remainingController.senderAmount.value) <=
                            controller.monthlyLimit.value
                    ? CustomColor.primaryLightColor
                    : CustomColor.primaryLightColor.withValues(alpha: 0.3),
                title: Strings.makePayment,
                onPressed: () {
                  if (controller.isValidUser.value &&
                      double.parse(controller
                              .remainingController.senderAmount.value) <=
                          controller.dailyLimit.value &&
                      double.parse(controller
                              .remainingController.senderAmount.value) <=
                          controller.monthlyLimit.value) {
                    controller.makePaymentProcess().then(
                          (value) => StatusScreen.show(
                            // ignore: use_build_context_synchronously
                            context: context,
                            subTitle: controller
                                .makePaymentModelData.message.success.first,
                            showAppName: false,
                            onPressed: () {
                              Get.offAllNamed(Routes.bottomNavBarScreen);
                            },
                          ),
                        );
                  }
                },
              ),
      ),
    );
  }
}
