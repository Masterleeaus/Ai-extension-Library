import 'dart:io';

import 'package:intl/intl.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';

import '../../../backend/model/categories/virtual_card/virtual_card_sudo/identiti_type.dart';
import '../../../controller/categories/virtual_card/sudo_virtual_card/sudo_adfund_controller.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../payment_link/custom_drop_down.dart';
import '../custom_input_dropdown.dart';
import '../limit_information_widget.dart';
import '../limit_widget.dart';

class SudoAddFundCustomAmountWidget extends StatelessWidget {
  SudoAddFundCustomAmountWidget({
    super.key,
    required this.buttonText,
    required this.onTap,
  });
  final String buttonText;
  final VoidCallback onTap;
  final controller = Get.put(SudoAddFundController());

  @override
  Widget build(BuildContext context) {
    return _bodyWidget(context);
  }

  _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.marginSizeHorizontal * 0.8,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          _inputFields(context),
          _chargeAndFee(context),
          if (buttonText == Strings.createCard) ...[
            _limitInformation(context),
          ],
          _buttonWidget(context)
        ],
      ),
    );
  }

  _inputFields(BuildContext context) {
    return Column(
      children: [
        Container(
          margin: EdgeInsets.only(top: Dimensions.paddingSize),
          child: CustomInputWithDropDown(
            controller: controller.virtualCardController.fundAmountController,
            hint: Strings.zero00,
            label: Strings.amount,
            selectedItem:
                controller.virtualCardController.selectedSupportedCurrency,
            itemList: controller.virtualCardController.supportedCurrencyList,
            displayItem: (item) => item.currencyCode,
            onDropChanged: (value) {
              controller.virtualCardController.selectedSupportedCurrency.value =
                  value;
              controller.virtualCardController.selectedCurrencyCode.value =
                  value!.currencyCode;
              controller.virtualCardController.updateLimit();
              controller.virtualCardController.calculation();
            },
            onFieldChanged: (value) {
              controller.virtualCardController.calculation();
            },
          ),
        ),
        verticalSpace(Dimensions.marginBetweenInputBox * 0.2),
        if (buttonText == Strings.createCard) ...[
          _datePicker(context),
          _identityType(context),
          _othersInput(context),
        ],
        // _fromWallet(context),
      ],
    );
  }

  _chargeAndFee(BuildContext context) {
    return Obx(
      () => Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingVerticalSize * 0.4),
        child: LimitWidget(
          fee:
              '${controller.virtualCardController.fixedCharge.value.toStringAsFixed(4)} ',
          limit:
              '${controller.virtualCardController.limitMin} - ${controller.virtualCardController.limitMax}',
        ),
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        top: Platform.isAndroid ? Dimensions.marginSizeVertical * 1.8 : 0.0,
      ),
      child: Row(
        mainAxisAlignment: mainCenter,
        children: [
          Obx(
            () => controller.isLoading
                ? const CustomLoadingAPI()
                : Expanded(
                    child: PrimaryButton(
                      title: buttonText,
                      onPressed: onTap,
                      borderColor: CustomColor.primaryLightColor,
                      buttonColor: CustomColor.primaryLightColor,
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  // _fromWallet(BuildContext context) {
  //   return CustomDropDown<UserWallet>(
  //     dropDownHeight: Dimensions.inputBoxHeight * 0.9,
  //     items: controller.virtualCardController.walletsList,
  //     title: Strings.fromWallet,
  //     hint: "balance",
  //     onChanged: (value) {
  //       controller.virtualCardController.selectMainWallet.value =
  //           value!.currency.code;
  //     },
  //     padding: EdgeInsets.symmetric(
  //       horizontal: Dimensions.paddingHorizontalSize * 0.25,
  //     ),
  //     titleTextColor: CustomColor.primaryLightTextColor,
  //     borderEnable: true,
  //     dropDownFieldColor: Colors.transparent,
  //     dropDownIconColor: CustomColor.primaryLightTextColor,
  //   );
  // }

  _othersInput(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        PrimaryInputWidget(
          controller: controller.identityNumberController,
          hint: Strings.enterIdentityNumber.tr,
          label: Strings.identityNumber.tr,
        ),
        verticalSpace(Dimensions.heightSize * 0.7),
        PrimaryInputWidget(
          controller: controller.phoneController,
          hint: Strings.enterPhone.tr,
          label: Strings.phone.tr,
        ),
        verticalSpace(Dimensions.heightSize * 0.7),
        PrimaryInputWidget(
          readOnly: true,
          controller: controller.virtualCardController.fromController,
          hint: "",
          label: Strings.fromWallet.tr,
        ),
      ],
    );
  }

  _datePicker(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.heightSize * 0.5,
      ),
      child: GestureDetector(
        onTap: () async {
          DateTime? pickedDate = await showDatePicker(
            context: Get.context!,
            initialDate: DateTime.now(),
            firstDate: DateTime(1920),
            lastDate: DateTime(2100),
          );
          if (pickedDate != null) {
            final DateFormat formatter = DateFormat('yyyy/MM/dd');
            final String formattedDate = formatter.format(pickedDate);
            controller.birthdateController.text = formattedDate;
          }
        },
        child: AbsorbPointer(
          child: PrimaryInputWidget(
            controller: controller.birthdateController,
            label: Strings.dateOfBirth,
            hint: Strings.enterDateOfBirth,
            isValidator: true,
          ),
        ),
      ),
    );
  }

  _identityType(BuildContext context) {
    return Obx(
      () => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CustomDropDown<IdentityTypeModel>(
            // dropDownHeight: Dimensions.inputBoxHeight * 0.9,
            items: controller.identityTypeList,
            title: Strings.identityType,
            hint: controller.selectIdentityType.value!.label,
            onChanged: (value) {
              controller.selectIdentityType.value = value!;
            },
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.paddingHorizontalSize * 0.25,
            ),
            titleTextColor: CustomColor.primaryLightTextColor,
            borderEnable: true,
            dropDownFieldColor: Colors.transparent,
            dropDownIconColor: CustomColor.primaryLightTextColor,
          ),
          verticalSpace(Dimensions.heightSize * 0.7),
        ],
      ),
    );
  }

  _limitInformation(BuildContext context) {
    return LimitInformationWidget(
      showDailyLimit: controller.virtualCardController.dailyLimit.value == 0.0
          ? false
          : true,
      showMonthlyLimit:
          controller.virtualCardController.monthlyLimit.value == 0.0
              ? false
              : true,
      transactionLimit:
          '${controller.virtualCardController.limitMin.value} - ${controller.virtualCardController.limitMax.value} ${controller.virtualCardController.baseCurrency.value}',
      dailyLimit:
          '${controller.virtualCardController.dailyLimit.value} ${controller.virtualCardController.baseCurrency.value}',
      monthlyLimit:
          '${controller.virtualCardController.monthlyLimit.value} ${controller.virtualCardController.baseCurrency.value}',
      remainingMonthLimit:
          '${controller.virtualCardController.remainingController.remainingMonthLyLimit.value} ${controller.virtualCardController.baseCurrency.value}',
      remainingDailyLimit:
          '${controller.virtualCardController.remainingController.remainingDailyLimit.value} ${controller.virtualCardController.baseCurrency.value}',
    );
  }
}
