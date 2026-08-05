import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../language/language_controller.dart';
import '../../utils/basic_screen_imports.dart';
import '../inputs/decimal_formater_widget.dart';

class CustomInputWithDropDown<T> extends StatefulWidget {
  final String hint, icon, label;
  final int maxLines;
  final bool isValidator;
  final EdgeInsetsGeometry? paddings;
  final TextEditingController controller;
  final Rxn<T> selectedItem;
  final Function(String)? onFieldChanged;
  final Function(T?)? onDropChanged;
  final List<T> itemList;
  final String Function(T) displayItem;

  const CustomInputWithDropDown({
    super.key,
    required this.controller,
    required this.hint,
    this.icon = "",
    this.isValidator = true,
    this.maxLines = 1,
    this.paddings,
    required this.label,
    required this.selectedItem,
    this.onFieldChanged,
    this.onDropChanged,
    required this.itemList,
    required this.displayItem,
  });

  @override
  State<CustomInputWithDropDown> createState() =>
      _CustomInputWithDropDownState<T>();
}

class _CustomInputWithDropDownState<T>
    extends State<CustomInputWithDropDown<T>> {
  FocusNode? focusNode;

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

  final languageController = Get.put(LanguageController());

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
                textInputAction: TextInputAction.done,
                controller: widget.controller,
                onTap: () {
                  setState(() {
                    focusNode!.requestFocus();
                  });
                },
                onChanged: widget.onFieldChanged,
                onFieldSubmitted: (value) {
                  setState(() {
                    focusNode!.unfocus();
                  });
                },
                focusNode: focusNode,
                textAlign: TextAlign.left,
                style: Get.isDarkMode
                    ? CustomStyle.darkHeading3TextStyle
                    : CustomStyle.lightHeading3TextStyle,
                inputFormatters: <TextInputFormatter>[
                  DecimalTextInputFormatter()
                ],
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                maxLines: widget.maxLines,
                decoration: InputDecoration(
                  hintText: languageController.getTranslation(widget.hint),
                  hintStyle: GoogleFonts.inter(
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w500,
                    color: Get.isDarkMode
                        ? CustomColor.primaryDarkTextColor
                            .withValues(alpha: 0.2)
                        : CustomColor.primaryTextColor.withValues(alpha: 0.2),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: BorderSide(
                      color:
                          CustomColor.primaryLightColor.withValues(alpha: 0.2),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * 0.5),
                    borderSide: BorderSide(
                        width: 2, color: CustomColor.primaryLightColor),
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
                    horizontal: Dimensions.widthSize * 1.2,
                    vertical: Dimensions.heightSize,
                  ),
                  suffixIcon: Container(
                    height: Dimensions.inputBoxHeight,
                    padding: EdgeInsets.only(left: Dimensions.widthSize * 0.5),
                    alignment: Alignment.centerRight,
                    width: isTablet()
                        ? Dimensions.widthSize * 6
                        : Dimensions.widthSize * 7.5,
                    decoration: BoxDecoration(
                        color: CustomColor.primaryLightColor,
                        borderRadius: BorderRadius.only(
                          topRight: Radius.circular(Dimensions.radius * 0.5),
                          bottomRight: Radius.circular(Dimensions.radius * 0.5),
                        )),
                    child: Obx(
                      () => Padding(
                        padding: const EdgeInsets.only(right: 10),
                        child: DropdownButton<T>(
                          hint: Text(
                            widget.displayItem(widget.selectedItem.value as T),
                            style: GoogleFonts.inter(
                              color: CustomColor.whiteColor,
                              fontSize: Dimensions.headingTextSize4,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          iconEnabledColor: CustomColor.whiteColor,
                          iconSize: Dimensions.heightSize * 1.5,
                          dropdownColor: CustomColor.primaryLightColor,
                          underline: Container(),
                          items: widget.itemList.map<DropdownMenuItem<T>>(
                            (value) {
                              return DropdownMenuItem<T>(
                                value: value,
                                child: Text(
                                  widget.displayItem(value),
                                  style: GoogleFonts.inter(
                                    color: CustomColor.whiteColor,
                                    fontSize: Dimensions.headingTextSize4,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              );
                            },
                          ).toList(),
                          onChanged: widget.onDropChanged,
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
