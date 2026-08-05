import '../../../utils/basic_screen_imports.dart';

abstract class DropdownModel {
  String get title;
}

class CustomDropDown<T extends DropdownModel> extends StatefulWidget {
  final String? hint;
  final String title;
  final Color? borderColor;
  final List<T> items;
  final void Function(T?) onChanged;
  final BoxBorder? border;
  final double? fieldBorderRadius;
  final Color? dropDownIconColor;
  final Color? titleTextColor;
  final Color dropDownFieldColor;
  final Color? dropDownColor;
  final bool isExpanded;
  final bool borderEnable;
  final EdgeInsetsGeometry? padding;
  final EdgeInsetsGeometry? margin;
  final TextStyle? titleStyle;
  final BorderRadiusGeometry? customBorderRadius;
  final bool? isCurrencyDropDown;

  const CustomDropDown({
    super.key,
    required this.items,
    required this.onChanged,
    this.border,
    this.fieldBorderRadius,
    this.dropDownIconColor,
    this.titleTextColor,
    this.dropDownFieldColor = Colors.transparent,
    this.isExpanded = true,
    this.padding,
    this.margin,
    this.titleStyle,
    this.borderColor,
    this.dropDownColor,
    this.hint,
    this.borderEnable = true,
    this.title = '',
    this.customBorderRadius,
    this.isCurrencyDropDown = false,
  });

  @override
  // ignore: library_private_types_in_public_api
  _CustomDropDownState<T> createState() => _CustomDropDownState<T>();
}

class _CustomDropDownState<T extends DropdownModel>
    extends State<CustomDropDown<T>> {
  T? _selectedItem;

  @override
  Widget build(BuildContext context) {
    return widget.title != ''
        ? Visibility(
            visible: widget.title != '',
            child: Column(
              crossAxisAlignment: crossStart,
              mainAxisSize: mainMin,
              children: [
                TitleHeading4Widget(
                  text: widget.title,
                  fontWeight: FontWeight.w600,
                  fontSize: Dimensions.headingTextSize3,
                ).paddingOnly(bottom: Dimensions.marginBetweenInputTitleAndBox),
                _dropDown()
              ],
            ))
        : _dropDown();
  }

  _dropDown() {
    return Container(
      height: Dimensions.inputBoxHeight * 0.69,
      padding: widget.padding ?? EdgeInsets.only(left: Dimensions.widthSize),
      margin: widget.margin,
      decoration: BoxDecoration(
        //color: widget.dropDownFieldColor,
        border: widget.borderEnable
            ? widget.border ??
                Border.all(
                  color: widget.borderColor ??
                      (_selectedItem != null
                          ? CustomColor.primaryLightColor.withOpacity(0.2)
                          : CustomColor.primaryLightColor.withOpacity(0.2)),
                  width: 1.5,
                )
            : null,
        borderRadius: widget.customBorderRadius ??
            BorderRadius.circular(
              widget.fieldBorderRadius ?? Dimensions.radius * 0.5,
            ),
      ),
      child: DropdownButtonHideUnderline(
        child: Padding(
          padding: widget.padding ?? const EdgeInsets.only(left: 10, right: 20),
          child: DropdownButton<T>(
            hint: Text(
              widget.hint ?? '',
              style: CustomStyle.darkHeading3TextStyle.copyWith(
                fontSize: Dimensions.headingTextSize3,
                fontWeight: FontWeight.w500,
                color: widget.isCurrencyDropDown!
                    ? CustomColor.whiteColor
                    : CustomColor.primaryTextColor,
              ),
            ),
            value: _selectedItem,
            icon: Padding(
              padding: const EdgeInsets.only(right: 4),
              child: Icon(
                Icons.arrow_drop_down_rounded,
                color: widget.dropDownIconColor ??
                    (_selectedItem != null
                        ? CustomColor.primaryLightColor
                        : CustomColor.primaryLightColor),
              ),
            ),
            style: TextStyle(
              color: widget.titleTextColor ?? CustomColor.primaryLightColor,
              fontSize: Dimensions.headingTextSize3,
              fontWeight: FontWeight.w500,
            ),
            dropdownColor: widget.dropDownColor ?? CustomColor.whiteColor,
            isExpanded: widget.isExpanded,
            menuMaxHeight: MediaQuery.sizeOf(context).height * .4,
            underline: Container(),
            borderRadius: BorderRadius.circular(Dimensions.radius),
            onChanged: (T? newValue) {
              setState(() {
                _selectedItem = newValue;
                widget.onChanged(_selectedItem);
              });
            },
            items: widget.items.map<DropdownMenuItem<T>>(
              (T value) {
                return DropdownMenuItem<T>(
                  value: value,
                  child: Text(
                    value.title,
                    style: CustomStyle.lightHeading3TextStyle,
                  ),
                );
              },
            ).toList(),
          ),
        ),
      ),
    );
  }
}
