import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/controller/categories/deposit/deposti_controller.dart';
import 'package:qrpay/widgets/text_labels/custom_title_heading_widget.dart';
import 'package:qrpay/widgets/text_labels/title_heading4_widget.dart';

import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../buttons/primary_button.dart';
import '../../text_labels/title_heading5_widget.dart';
import '../limit_with_exchange_rate_widget.dart';

class CustomAmountWidget extends StatelessWidget {
  CustomAmountWidget({
    super.key,
    required this.buttonText,
    required this.onTap,
    required this.isLoading,
  });
  final String buttonText;
  final VoidCallback onTap;
  final controller = Get.put(DepositController());
  final RxBool isLoading;

  @override
  Widget build(BuildContext context) {
    return _bodyWidget(context);
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      children: [
        _inputFieldWidget(context),
        _minMaxWidget(),
        _walletDropDownWidget(context),
        _customNumKeyBoardWidget(context),
        _buttonWidget(context)
      ],
    );
  }

  _inputFieldWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        right: Dimensions.marginSizeHorizontal * 0.5,
        top: Dimensions.marginSizeVertical,
      ),
      alignment: Alignment.topCenter,
      height: Dimensions.inputBoxHeight,
      width: double.infinity,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Directionality(
            textDirection: TextDirection.rtl,
            child: SizedBox(
              width: MediaQuery.of(context).size.width * 0.60,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(width: Dimensions.widthSize * 0.7),
                  Expanded(
                    child: TextFormField(
                      style: Get.isDarkMode
                          ? CustomStyle.darkHeading2TextStyle.copyWith(
                              fontSize: Dimensions.headingTextSize3 * 2,
                            )
                          : CustomStyle.darkHeading2TextStyle.copyWith(
                              color: CustomColor.primaryLightColor,
                              fontSize: Dimensions.headingTextSize3 * 2,
                            ),
                      readOnly: true,
                      controller: controller.amountTextController,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      inputFormatters: <TextInputFormatter>[
                        FilteringTextInputFormatter.allow(
                            RegExp(r'(^-?\d*\.?\d*)')),
                        LengthLimitingTextInputFormatter(
                            6), //max length of 12 characters
                      ],
                      validator: (String? value) {
                        if (value!.isEmpty) {
                          return null;
                        } else {
                          return Strings.pleaseFillOutTheField;
                        }
                      },
                      decoration: const InputDecoration(
                        enabledBorder: InputBorder.none,
                        focusedBorder: InputBorder.none,
                        errorBorder: InputBorder.none,
                        focusedErrorBorder: InputBorder.none,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                  ),
                  SizedBox(width: Dimensions.widthSize * 0.5),
                ],
              ),
            ),
          ),
          SizedBox(width: Dimensions.widthSize * 0.7),
          _currencyDropDownWidget(context),
        ],
      ),
    );
  }

  _customNumKeyBoardWidget(BuildContext context) {
    return GridView.count(
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 3,
      crossAxisSpacing: 10.0,
      mainAxisSpacing: 10.0,
      childAspectRatio: 3 / 1.7,
      shrinkWrap: true,
      children: List.generate(
        controller.keyboardItemList.length,
        (index) {
          return controller.inputItem(index);
        },
      ),
    );
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        left: Dimensions.marginSizeHorizontal * 0.8,
        right: Dimensions.marginSizeHorizontal * 0.8,
        top: Platform.isAndroid ? Dimensions.marginSizeVertical * 1.8 : 0.0,
      ),
      child: Row(
        children: [
          Expanded(
            child: isLoading.value
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: buttonText,
                    onPressed: onTap,
                    borderColor: CustomColor.primaryLightColor,
                    buttonColor: CustomColor.primaryLightColor,
                  ),
          ),
        ],
      ),
    );
  }

  _walletDropDownWidget(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Obx(() {
      return Container(
        height: isTablet()
            ? Dimensions.buttonHeight * 0.9
            : Dimensions.buttonHeight * 0.6,
        alignment: Alignment.center,
        margin: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 2.5,
          vertical: Dimensions.marginSizeVertical * 1.5,
        ),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.radius * 3),
          color: CustomColor.primaryLightColor,
        ),
        child: DropdownButton(
          menuMaxHeight: MediaQuery.sizeOf(context).height * 0.5,
          underline: Container(),
          hint: TitleHeading4Widget(
            text: controller.selectedCurrencyName.value,
            fontSize: isTablet()
                ? Dimensions.headingTextSize3
                : Dimensions.headingTextSize2,
            color: CustomColor.whiteColor,
            fontWeight: FontWeight.w500,
          ),
          icon: Icon(
            Icons.arrow_drop_down_rounded,
            color: CustomColor.whiteColor,
            size: isTablet()
                ? Dimensions.iconSizeLarge * 1.4
                : Dimensions.iconSizeLarge,
          ),
          items: controller.currencyList.map<DropdownMenuItem<String>>((value) {
            return DropdownMenuItem<String>(
              onTap: () {
                controller.remainingController.cardId.value = value.id;
                controller.remainingController.getRemainingBalanceProcess();
                controller.selectedCurrencyAlias.value = value.alias;
                controller.selectedCurrencyType.value = value.type;
                controller.selectedCurrencyId.value = value.id;
                controller.currencyWalletCode.value = value.currencyCode;
                controller.crypto.value = value.crypto;
                controller.rate.value = double.parse(value.rate);

                controller.fee.value = double.parse(value.fixedCharge);
                controller.limitMin.value =
                    double.parse(value.minLimit) / controller.rate.value;
                controller.limitMax.value =
                    double.parse(value.maxLimit) / controller.rate.value;
                controller.percentCharge.value =
                    double.parse(value.percentCharge);
              },
              value: value.name,
              child: Container(
                alignment: Alignment.centerLeft,
                height: 40.h,
                child: Row(
                  children: [
                    SizedBox(
                      width: Dimensions.widthSize * 0.2,
                    ),
                    Text(
                      value.name,
                      style: TextStyle(
                        color:
                            controller.selectedCurrencyName.value == value.name
                                ? CustomColor.primaryLightColor
                                : CustomColor.primaryLightColor,
                        fontSize: Dimensions.headingTextSize4,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
          onChanged: (String? value) {
            controller.selectedCurrencyName.value = value!;
          },
        ),
      );
    });
  }

  _currencyDropDownWidget(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Obx(() {
      return Container(
        width: MediaQuery.of(context).size.width * 0.22,
        height: isTablet()
            ? Dimensions.buttonHeight
            : Dimensions.buttonHeight * 0.65,
        alignment: Alignment.center,
        margin: EdgeInsets.symmetric(
          horizontal: Dimensions.marginSizeHorizontal * 0.1,
          vertical: Dimensions.marginSizeVertical * 0.2,
        ),
        decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(Dimensions.radius * 3),
            color: CustomColor.primaryLightColor),
        child: DropdownButton(
          underline: Container(),
          hint: TitleHeading4Widget(
            text: controller.baseCurrency.value,
            fontSize: isTablet()
                ? Dimensions.headingTextSize3
                : Dimensions.headingTextSize2,
            color: CustomColor.whiteColor,
            fontWeight: FontWeight.w500,
          ),
          icon: Icon(
            Icons.arrow_drop_down_rounded,
            color: CustomColor.whiteColor,
            size: isTablet()
                ? Dimensions.iconSizeLarge * 1.4
                : Dimensions.iconSizeLarge,
          ),
          items: controller.baseCurrencyList
              .map<DropdownMenuItem<String>>((value) {
            return DropdownMenuItem<String>(
              value: value.toString(),
              child: Container(
                alignment: Alignment.centerLeft,
                height: 40.h,
                child: CustomTitleHeadingWidget(
                  text: value.toString(),
                  style: GoogleFonts.inter(
                    color: controller.baseCurrency.value == value
                        ? CustomColor.primaryLightColor
                        : CustomColor.primaryLightColor,
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            );
          }).toList(),
          onChanged: (String? value) {
            controller.baseCurrency.value = value!;
          },
        ),
      );
    });
  }

  _minMaxWidget() {
    return Obx(
      () => Center(
        child: Column(
          children: [
            LimitWithExchangeRateWidget(
              exchangeRate:
                  "1 ${controller.baseCurrency.value} = ${(controller.crypto.value == 1 ? controller.rate.value.toStringAsFixed(8) : controller.rate.value.toStringAsFixed(2))} ${controller.currencyWalletCode.value}",
              fee:
                  "${controller.fee.value} + ${controller.percentCharge.value}% ${controller.currencyWalletCode.value}",
              limit:
                  "${(controller.crypto.value == 1 ? controller.limitMin.value.toStringAsFixed(8) : controller.limitMin.value.toStringAsFixed(2))} ~ ${(controller.crypto.value == 1 ? controller.limitMax.value.toStringAsFixed(8) : controller.limitMax.value.toStringAsFixed(2))} ${controller.currencyCode.value}",
            ),
            Row(
              mainAxisAlignment: mainCenter,
              children: [
                TitleHeading5Widget(
                  text: Strings.remainingDailyLimit,
                  color: CustomColor.primaryLightColor.withValues(alpha: 0.6),
                ),
                horizontalSpace(Dimensions.widthSize),
                TitleHeading5Widget(
                    color: CustomColor.primaryLightColor.withValues(alpha: 0.6),
                    text:
                        ": ${controller.remainingController.remainingDailyLimit.value} ${controller.baseCurrency.value}"),
              ],
            ),
            Row(
              mainAxisAlignment: mainCenter,
              children: [
                TitleHeading5Widget(
                    color: CustomColor.primaryLightColor.withValues(alpha: 0.6),
                    text: Strings.remainingMonthlyLimit),
                horizontalSpace(Dimensions.widthSize),
                TitleHeading5Widget(
                    color: CustomColor.primaryLightColor.withValues(alpha: 0.6),
                    text:
                        ": ${controller.remainingController.remainingMonthLyLimit.value} ${controller.baseCurrency.value}"),
              ],
            )
          ],
        ),
      ),
    );
  }
}
