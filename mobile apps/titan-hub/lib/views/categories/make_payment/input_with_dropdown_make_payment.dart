import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:qrpay/utils/size.dart';

import '../../../controller/categories/make_payment/make_payment_controller.dart';
import '../../../language/english.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/inputs/decimal_formater_widget.dart';
import '../../../widgets/text_labels/title_heading4_widget.dart';

class MakePaymentInputWithDropdown extends StatefulWidget {
  final String hint, icon, label;
  final int maxLines;
  final bool isValidator;
  final EdgeInsetsGeometry? paddings;
  final TextEditingController controller;

  const MakePaymentInputWithDropdown({
    super.key,
    required this.controller,
    required this.hint,
    this.icon = "",
    this.isValidator = true,
    this.maxLines = 1,
    this.paddings,
    required this.label,
  });

  @override
  State<MakePaymentInputWithDropdown> createState() =>
      _PrimaryInputWidgetState();
}

class _PrimaryInputWidgetState extends State<MakePaymentInputWithDropdown> {
  FocusNode? focusNode;
  final currencyController = Get.put(MakePaymentController());

  @override
  void initState() {
    super.initState();
    focusNode = FocusNode();
  }

  @override
  void dispose() {
    focusNode!.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    bool isTablet() {
      return MediaQuery.of(context).size.shortestSide >= 600;
    }

    return Column(
      mainAxisAlignment: MainAxisAlignment.start,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TitleHeading4Widget(
          text: widget.label,
          fontWeight: FontWeight.w600,
        ),
        verticalSpace(Dimensions.marginBetweenInputTitleAndBox),
        Row(
          children: [
            Expanded(
              flex: 9,
              child: TextFormField(
                validator: widget.isValidator == false
                    ? null
                    : (String? value) {
                        if (value!.isEmpty) {
                          return Strings.pleaseFillOutTheField;
                        } else {
                          return null;
                        }
                      },
                onChanged: (v) {
                  currencyController.getFee(
                      rate: currencyController.baseCurrRate.value);
                  if (currencyController.amountController.text.isEmpty) {
                    currencyController.amountController.text = "";
                  } else {
                    currencyController.remainingController.senderAmount.value =
                        currencyController.amountController.text;
                    currencyController.remainingController
                        .getRemainingBalanceProcess();
                  }
                },
                textInputAction: TextInputAction.done,
                controller: widget.controller,
                // onSaved: (value) {},
                onTap: () {
                  setState(() {
                    focusNode!.requestFocus();
                  });
                },
                onFieldSubmitted: (value) {
                  setState(() {
                    focusNode!.unfocus();
                  });
                },
                focusNode: focusNode,
                textAlign: TextAlign.left,
                style: CustomStyle.darkHeading3TextStyle.copyWith(
                  color: Get.isDarkMode
                      ? CustomColor.primaryDarkTextColor
                      : CustomColor.primaryTextColor,
                ),
                inputFormatters: <TextInputFormatter>[
                  DecimalTextInputFormatter(decimalRange: 2)
                ],
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                maxLines: widget.maxLines,
                decoration: InputDecoration(
                  hintText: widget.hint,
                  hintStyle: GoogleFonts.inter(
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w500,
                    color: Get.isDarkMode
                        ? CustomColor.primaryDarkTextColor.withOpacity(0.2)
                        : CustomColor.primaryTextColor.withOpacity(0.2),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: BorderSide(
                      color: CustomColor.primaryLightColor.withOpacity(0.2),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: BorderSide(
                      width: 2,
                      color: CustomColor.primaryLightColor,
                    ),
                  ),
                  errorBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: const BorderSide(color: Colors.red, width: 2),
                  ),
                  focusedErrorBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: const BorderSide(color: Colors.red, width: 2),
                  ),
                  contentPadding: EdgeInsets.symmetric(
                    horizontal: Dimensions.widthSize * 1.7,
                    vertical: Dimensions.heightSize,
                  ),
                  suffixIcon: Obx(
                    () => Container(
                      padding: const EdgeInsets.only(left: 5),
                      height: Dimensions.inputBoxHeight,
                      alignment: Alignment.center,
                      width: isTablet()
                          ? Dimensions.widthSize * 6
                          : Dimensions.widthSize * 7.5,
                      decoration: BoxDecoration(
                          color: CustomColor.primaryLightColor,
                          borderRadius: BorderRadius.only(
                            topRight: Radius.circular(Dimensions.radius * 0.5),
                            bottomRight:
                                Radius.circular(Dimensions.radius * 0.5),
                          )),
                      child: Padding(
                        padding: const EdgeInsets.only(right: 10),
                        child: DropdownButton(
                          iconEnabledColor: CustomColor.whiteColor,
                          iconSize: Dimensions.heightSize * 1.5,
                          dropdownColor: CustomColor.primaryLightColor,
                          underline: Container(),
                          items: currencyController.baseCurrencyList
                              .map<DropdownMenuItem<String>>(
                            (value) {
                              return DropdownMenuItem<String>(
                                value: value,
                                child: Text(
                                  value,
                                  style: GoogleFonts.inter(
                                    color: CustomColor.whiteColor,
                                    fontSize: 16.sp,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              );
                            },
                          ).toList(),
                          onChanged: (String? value) {
                            currencyController.baseCurrency.value = value!;
                          },
                          value: currencyController.baseCurrency.value,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        )
      ],
    );
  }
}
