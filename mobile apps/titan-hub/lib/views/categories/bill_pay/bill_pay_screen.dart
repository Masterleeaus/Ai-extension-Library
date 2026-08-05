import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';
import 'package:qrpay/widgets/inputs/input_with_text.dart';
import 'package:qrpay/widgets/inputs/primary_input_filed.dart';
import 'package:qrpay/widgets/others/limit_widget.dart';

import '../../../backend/model/categories/bill_pay_model/bill_pay_model.dart';
import '../../../controller/categories/bill_pay/bill_pay_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../widgets/dropdown/custom_dropdown_menu.dart';
import '../../../widgets/others/congratulation_widget.dart';
import '../../../widgets/others/limit_information_widget.dart';
import '../../../widgets/text_labels/custom_title_heading_widget.dart';

class BillPayScreen extends StatelessWidget {
  BillPayScreen({super.key});

  final controller = Get.put(BillPayController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.billPay),
        body: Obx(() => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context)),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async {
        controller.getBillPayInfoData();
      },
      child: ListView(
        padding: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal,
        ),
        children: [
          _inputWidget(context),
          _buttonWidget(context),
        ],
      ),
    );
  }

  // Drop down input, bill number input ,and amount input
  _inputWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.marginSizeVertical * 1.5),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          CustomTitleHeadingWidget(
            text: Strings.billType.tr,
            style: CustomStyle.darkHeading4TextStyle.copyWith(
              fontWeight: FontWeight.w600,
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor.withOpacity(0.7)
                  : CustomColor.primaryTextColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 0.5),
          CustomDropdownMenu<BillType>(
            itemsList: controller.billList,
            selectMethod: controller.billMethodselected,
            onChanged: ((v) {
              controller.billMethodselected.value = v!.name!;
              controller.automaticCharge.value = v.localTransactionFee!;
              controller.selectedCurrency.value = v.receiverCurrencyCode!;
              controller.automaticLimitMin.value = v.minLocalTransactionAmount!;
              controller.automaticLimitMax.value = v.maxLocalTransactionAmount!;

              // Rate & currency get
              controller.automaticRate.value = v.receiverCurrencyRate!;
              controller.automaticSelectedCurrency.value =
                  v.receiverCurrencyCode!;
              controller.getExchangeRate(r: v.receiverCurrencyRate);
              if (v.itemType == "AUTOMATIC") {
                controller.isAutomatic.value = true;
              } else {
                controller.isAutomatic.value = false;
              }
            }),
          ),
          verticalSpace(Dimensions.heightSize),
          _billMonthWidget(context),
          PrimaryInputWidget(
            controller: controller.billNumberController,
            keyboardType: TextInputType.number,
            hint: Strings.enterBillNumber.tr,
            label: Strings.billNumber,
          ),
          verticalSpace(Dimensions.heightSize),
          Obx(
            () => InputWithText(
              controller: controller.amountController,
              hint: Strings.zero00,
              label: Strings.amount.tr,
              suffixText: controller.isAutomatic.value
                  ? controller.automaticSelectedCurrency.value
                  : controller.baseCurrency.value,
              onChanged: (v) {
                controller.getFee(rate: controller.rate.value);
              },
            ),
          ),
          Obx(
            () {
              return controller.isAutomatic.value
                  ? LimitWidget(
                      fee:
                          '${controller.totalFee.value.toStringAsFixed(4)} ${controller.automaticSelectedCurrency.value}',
                      limit:
                          '${controller.automaticLimitMin} - ${controller.automaticLimitMax} ${controller.automaticSelectedCurrency.value}')
                  : LimitWidget(
                      fee:
                          '${controller.totalFee.value.toStringAsFixed(4)} ${controller.baseCurrency.value}',
                      limit:
                          '${controller.limitMin} - ${controller.limitMax} ${controller.baseCurrency.value}',
                    );
            },
          ),

            LimitInformationWidget(
            showDailyLimit: controller.dailyLimit.value == 0.0 ? false : true,
            showMonthlyLimit:
                controller.monthlyLimit.value == 0.0 ? false : true,
            transactionLimit:
                '${controller.limitMin.value} - ${controller.limitMax.value} ${controller.baseCurrency.value}',
            dailyLimit:
                '${controller.dailyLimit.value} ${controller.baseCurrency.value}',
            monthlyLimit:
                '${controller.monthlyLimit.value} ${controller.baseCurrency.value}',
            remainingMonthLimit:
                '${controller.remainingController.remainingMonthLyLimit.value } ${controller.baseCurrency.value}',
            remainingDailyLimit:
                '${controller.remainingController.remainingDailyLimit.value} ${controller.baseCurrency.value}',
          ),
        ],
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical * 2,
      ),
      child: controller.isInsertLoading
          ? const CustomLoadingAPI()
          : PrimaryButton(
              title: Strings.payBill.tr,
              onPressed: () {
                controller.type.value =
                    controller.getType(controller.billMethodselected.value)!;
                controller
                    .billPayApiProcess(
                        amount: controller.amountController.text,
                        billNumber: controller.billNumberController.text,
                        type: controller.type.value)
                    .then((value) {
                  if (context.mounted) {
                    StatusScreen.show(
                      context: context,
                      subTitle: Strings.yourBillPaySuccess.tr,
                      onPressed: () {
                        Get.offAllNamed(Routes.bottomNavBarScreen);
                      },
                    );
                  }
                });
              },
            ),
    );
  }

  _billMonthWidget(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        CustomTitleHeadingWidget(
          text: Strings.billMonths.tr,
          style: CustomStyle.darkHeading4TextStyle.copyWith(
            fontWeight: FontWeight.w600,
            color: Get.isDarkMode
                ? CustomColor.primaryDarkTextColor.withOpacity(0.7)
                : CustomColor.primaryTextColor,
          ),
        ),
        verticalSpace(Dimensions.heightSize * 0.5),
        CustomDropdownMenu<String>(
          itemsList: controller.billMonthsList,
          selectMethod: controller.selectedBillMonths,
          onChanged: ((v) => controller.selectedBillMonths.value = v!),
        ),
        verticalSpace(Dimensions.heightSize),
      ],
    );
  }
}
