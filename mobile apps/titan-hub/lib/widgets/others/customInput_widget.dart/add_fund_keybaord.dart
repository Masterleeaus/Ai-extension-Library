import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/backend/utils/custom_loading_api.dart';
import 'package:qrpay/controller/categories/virtual_card/flutter_wave_virtual_card/adfund_controller.dart';
import 'package:qrpay/utils/size.dart';
import 'package:qrpay/widgets/text_labels/custom_title_heading_widget.dart';
import 'package:qrpay/widgets/text_labels/title_heading4_widget.dart';

import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../buttons/primary_button.dart';
import '../limit_widget.dart';

class AddFundCustomAmountWidget extends StatelessWidget {
  AddFundCustomAmountWidget({
    super.key,
    required this.buttonText,
    required this.onTap,
  });
  final String buttonText;
  final VoidCallback onTap;
  final controller = Get.put(AddFundController());

  @override
  Widget build(BuildContext context) {
    return _bodyWidget(context);
  }

  _bodyWidget(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        _inputFieldWidget(context),
        _chargeAndFee(context),
        _customNumKeyBoardWidget(context),
        _buttonWidget(context)
      ],
    );
  }

  _inputFieldWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        right: Dimensions.marginSizeHorizontal * 0.5,
        top: Dimensions.marginSizeVertical * 2,
      ),
      alignment: Alignment.topCenter,
      height: Dimensions.inputBoxHeight,
      width: double.infinity,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: MediaQuery.of(context).size.width * 0.60,
            child: Directionality(
              textDirection: TextDirection.rtl,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(width: Dimensions.widthSize * 0.7),
                  Expanded(
                    child: TextFormField(
                      style: Get.isDarkMode
                          ? CustomStyle.lightHeading2TextStyle.copyWith(
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
                          6,
                        ), //max length of 12 characters
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
                  SizedBox(
                    width: Dimensions.widthSize * 0.5,
                  ),
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

  _chargeAndFee(BuildContext context) {
    return Obx(() => Row(
          mainAxisAlignment: mainCenter,
          children: [
            LimitWidget(
                fee:
                    '${controller.virtualCardController.totalFee.value.toStringAsFixed(4)} ${controller.virtualCardController.baseCurrency.value}',
                limit:
                    '${controller.virtualCardController.limitMin} - ${controller.virtualCardController.limitMax} ${controller.virtualCardController.baseCurrency.value}'),
          ],
        ));
  }

  _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        left: Dimensions.marginSizeHorizontal * 0.8,
        right: Dimensions.marginSizeHorizontal * 0.8,
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

  _currencyDropDownWidget(BuildContext context) {
    return Obx(() {
      return Container(
        width: MediaQuery.of(context).size.width * 0.21,
        height: Dimensions.buttonHeight * 0.65,
        alignment: Alignment.center,
        margin: EdgeInsets.symmetric(
            horizontal: Dimensions.marginSizeHorizontal * 0.1,
            vertical: Dimensions.marginSizeVertical * 0.2),
        decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(Dimensions.radius * 3),
            color: CustomColor.primaryLightColor),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            SizedBox(
              width: Dimensions.widthSize * 0.5,
            ),
            SizedBox(
              width: Dimensions.widthSize * 0.4,
            ),
            DropdownButton(
              underline: Container(),
              hint: TitleHeading4Widget(
                text: controller.virtualCardController.baseCurrency.value,
                fontSize: Dimensions.headingTextSize2,
                color: CustomColor.whiteColor,
                fontWeight: FontWeight.w500,
              ),
              icon: const Icon(
                Icons.arrow_drop_down_rounded,
                color: CustomColor.whiteColor,
              ),
              items: controller.virtualCardController.baseCurrencyList
                  .map<DropdownMenuItem<String>>((value) {
                return DropdownMenuItem<String>(
                  value: value.toString(),
                  child: Container(
                    alignment: Alignment.centerLeft,
                    height: 40.h,
                    child: Row(
                      children: [
                        CustomTitleHeadingWidget(
                          text: value.toString(),
                          style: GoogleFonts.inter(
                            color: controller.virtualCardController.baseCurrency
                                        .value ==
                                    value
                                ? CustomColor.primaryLightColor
                                : CustomColor.primaryLightColor,
                            fontSize: Dimensions.headingTextSize3,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
              onChanged: (String? value) {
                controller.virtualCardController.baseCurrency.value = value!;
              },
            ),
          ],
        ),
      );
    });
  }
}
