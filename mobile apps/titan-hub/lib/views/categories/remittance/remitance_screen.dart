import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:qrpay/utils/responsive_layout.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/appbar/appbar_widget.dart';
import 'package:qrpay/widgets/buttons/primary_button.dart';
import 'package:qrpay/widgets/others/limit_widget.dart';

import '../../../backend/model/remittance/remittance_get_recipient_model.dart';
import '../../../backend/model/remittance/remittance_info_model.dart';
import '../../../controller/categories/remittance/remitance_controller.dart';
import '../../../language/english.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../widgets/dropdown/custom_dropdown_menu.dart';
import '../../../widgets/inputs/input_with_text.dart';
import '../../../widgets/others/limit_information_widget.dart';
import '../../../widgets/text_labels/custom_title_heading_widget.dart';

class RemittanceScreen extends StatelessWidget {
  RemittanceScreen({super.key});

  final controller = Get.put(RemittanceController());
  final _formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.remittance),
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : _bodyWidget(context),
        ),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return Form(
      key: _formKey,
      child: ListView(
        physics: const BouncingScrollPhysics(),
        padding: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.9,
        ),
        children: [
          _countryInput(context),
          _dropdownInput(context),
          _selectedInputWidget(context),
          _buttonWidget(context),
        ],
      ),
    );
  }

  _countryInput(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.marginSizeVertical),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          CustomTitleHeadingWidget(
            text: Strings.sendingCountry,
            style: CustomStyle.darkHeading4TextStyle.copyWith(
              fontWeight: FontWeight.w600,
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor
                  : CustomColor.primaryTextColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 0.8),
          CustomDropdownMenu<Country>(
            selectMethod: controller.selectedSendingCountry,
            itemsList: controller.sendingCountryList,
            onChanged: (value) {
              controller.selectedSendingCountry.value = value!.country;
              controller.sendingCountryId.value = value.id;
              controller.selectedSendingCountryCode.value = value.code;
              controller.fromCountriesRate.value = value.rate;
              controller.recipientGet;
            },
          ),
          verticalSpace(Dimensions.heightSize * 0.5),
          CustomTitleHeadingWidget(
            text: Strings.receivingCountry,
            style: CustomStyle.darkHeading4TextStyle.copyWith(
              fontWeight: FontWeight.w600,
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor
                  : CustomColor.primaryTextColor,
            ),
          ),
          verticalSpace(Dimensions.heightSize * 0.8),
          CustomDropdownMenu<Country>(
            selectMethod: controller.selectedReceivingCountry,
            itemsList: controller.receivingCountryList,
            onChanged: (value) {
              controller.selectedReceivingCountry.value = value!.country;
              controller.receivingCountryId.value = value.id;
              controller.selectedReceivingCountryCode.value = value.code;
              controller.toCountriesRate.value = value.rate;
              controller.recipientGet;
              controller.remittanceGetRecipientProcess();
            },
          ),
        ],
      ),
    );
  }

  _dropdownInput(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        verticalSpace(Dimensions.heightSize * 0.5),
        //!receiving method
        CustomTitleHeadingWidget(
          text: Strings.receivingMethod,
          style: CustomStyle.labelTextStyle.copyWith(
            color: Get.isDarkMode
                ? CustomColor.primaryDarkTextColor
                : CustomColor.primaryTextColor,
          ),
        ),
        verticalSpace(Dimensions.heightSize * 0.5),
        CustomDropdownMenu<TransactionType>(
          onChanged: (value) {
            controller.selectedMethod.value = value!.labelName;
            controller.selectedTrxType.value = value.fieldName;
            controller.remittanceGetRecipientProcess();
          },
          itemsList: controller.transactionTypeList,
          selectMethod: controller.selectedMethod,
        ),
        verticalSpace(Dimensions.heightSize * 0.8),

        CustomTitleHeadingWidget(
            text: Strings.recipient,
            style: CustomStyle.labelTextStyle.copyWith(
              color: Get.isDarkMode
                  ? CustomColor.primaryDarkTextColor
                  : CustomColor.primaryTextColor,
            )),
        verticalSpace(Dimensions.heightSize * 0.5),
        Row(
          children: [
            Expanded(
              flex: 9,
              child: CustomDropdownMenu<RecipientInfo>(
                onChanged: (value) {
                  controller.selectedRecipient.value =
                      "${value!.firstname} ${value.lastname}";
                  controller.selectedRecipientId.value = value.id;
                },
                itemsList: controller.recipientList,
                selectMethod: controller.selectedRecipient,
              ),
            ),
            horizontalSpace(Dimensions.widthSize * 0.5),
            Expanded(
              flex: 3,
              child: GestureDetector(
                onTap: (() {
                  Get.toNamed(Routes.addRecipientScreen);
                }),
                child: Container(
                  height: Dimensions.heightSize * 3.2,
                  width: Dimensions.widthSize * 8.2,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    color: CustomColor.primaryLightColor,
                  ),
                  child: Container(
                    margin: EdgeInsets.symmetric(
                      horizontal: Dimensions.marginSizeHorizontal * 0.4,
                    ),
                    child: FittedBox(
                      child: CustomTitleHeadingWidget(
                        text: Strings.addPlus,
                        style: CustomStyle.lightHeading4TextStyle.copyWith(
                          fontWeight: FontWeight.w500,
                          fontSize: Dimensions.headingTextSize3,
                          color: CustomColor.whiteColor,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
        verticalSpace(Dimensions.heightSize * 0.9),
      ],
    );
  }

  _selectedInputWidget(BuildContext context) {
    var currency = controller.baseCurrency.value;
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        InputWithText(
          hint: Strings.zero00,
          suffixText: controller.selectedSendingCountryCode.value.toString(),
          controller: controller.amountController,
          label: Strings.amount,
          onChanged: (amount) {  
            if (controller.amountController.text.isEmpty) {                     
              controller.amountController.text = "";                                                                                                   
            } else {
              controller.remainingController.senderAmount.value =
                  controller.amountController.text;
              controller.remainingController.getRemainingBalanceProcess();   
            } 
            controller.recipientGet;                    
            controller.getFee(rate: controller.fromCountriesRate.value);
          },
        ),
        verticalSpace(Dimensions.heightSize * 0.3),
        LimitWidget(
            fee: "${controller.totalFee.value.toStringAsFixed(4)} $currency",
            limit:
                "${controller.minLimit.value.toString()} $currency - ${controller.maxLimit.value.toString()} $currency "),
        LimitInformationWidget(
          showDailyLimit: controller.dailyLimit.value == 0.0 ? false : true,
          showMonthlyLimit: controller.monthlyLimit.value == 0.0 ? false : true,
          transactionLimit: 
              '${controller.minLimit.value} - ${controller.maxLimit.value} ${controller.baseCurrency.value}',
          dailyLimit:
              '${controller.dailyLimit.value} ${controller.baseCurrency.value}',
          monthlyLimit:
              '${controller.monthlyLimit.value} ${controller.baseCurrency.value}',
          remainingMonthLimit:
              '${controller.remainingController.remainingMonthLyLimit.value} ${controller.baseCurrency.value}',
          remainingDailyLimit:
              '${controller.remainingController.remainingDailyLimit.value} ${controller.baseCurrency.value}',
        ),
        verticalSpace(Dimensions.heightSize * 0.5),
        InputWithText(
          hint: Strings.zero00,
          suffixText: controller.selectedReceivingCountryCode.value.toString(),               
          controller: controller.recipientGetController,
          label: Strings.recipientGet,
          onChanged: (amount) {
            controller.senderSendAmount;
          },
        ),                  
      ],
    );
  } 


  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical),
      child: PrimaryButton(
        buttonColor: double.parse(
                        controller.remainingController.senderAmount.value) >
                    0 &&
                double.parse(
                        controller.remainingController.senderAmount.value) <=
                    controller.dailyLimit.value &&
                double.parse(
                        controller.remainingController.senderAmount.value) <=
                    controller.monthlyLimit.value
            ? CustomColor.primaryLightColor
            : CustomColor.primaryLightColor.withValues(alpha: 0.3),
        title: Strings.send,
        onPressed: () {
          if (double.parse(controller.remainingController.senderAmount.value) >
                  0 &&
              double.parse(controller.remainingController.senderAmount.value) <=
                  controller.dailyLimit.value &&
              double.parse(controller.remainingController.senderAmount.value) <=
                  controller.monthlyLimit.value) {
            controller.togoRemittancePreview();
          }
        },
      ),
    );
  }
}
