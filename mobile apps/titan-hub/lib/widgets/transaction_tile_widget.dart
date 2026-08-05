// ignore_for_file: prefer_null_aware_operators

import 'package:flutter/material.dart';
import 'package:qrpay/widgets/text_labels/custom_title_heading_widget.dart';

import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';

class TransactionItemTileWidget extends StatelessWidget {
  const TransactionItemTileWidget(
      {super.key,
      required this.title,
      this.value = "",
      this.richText = "",
      this.statusText = "",
      this.color,
      this.richText2 = "",
      this.onTap,
      this.valueColor,
      this.textWrap = false});

  final String title, value, richText, richText2, statusText;
  final Color? color;
  final Color? valueColor;
  final VoidCallback? onTap;
  final bool textWrap;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: mainSpaceBet,
      crossAxisAlignment: crossCenter,
      children: [
        Row(
          crossAxisAlignment: crossCenter,
          mainAxisAlignment: mainStart,
          mainAxisSize: mainMin,
          children: [
            CustomTitleHeadingWidget(
                text: title,
                style: CustomStyle.whiteTextStyle.copyWith(
                    color: CustomColor.whiteColor.withOpacity(.4),
                    fontSize: Dimensions.headingTextSize4,
                    fontWeight: FontWeight.w400)),
            horizontalSpace(Dimensions.marginBetweenInputTitleAndBox),
            Visibility(
              visible: richText2.isNotEmpty,
              child: Container(
                padding: EdgeInsets.symmetric(
                  horizontal: Dimensions.paddingSize * .2,
                  vertical: Dimensions.paddingSize * .1,
                ),
                decoration: BoxDecoration(
                    color: CustomColor.yellowColor,
                    borderRadius:
                        BorderRadius.circular(Dimensions.radius * .4)),
                child: CustomTitleHeadingWidget(
                    text: richText2,
                    style: CustomStyle.yellowTextStyle
                        .copyWith(color: CustomColor.primaryTextColor)),
              ),
            )
          ],
        ),
        Row(
          crossAxisAlignment: crossCenter,
          mainAxisAlignment: mainEnd,
          mainAxisSize: mainMin,
          children: [
            CustomTitleHeadingWidget(
                text: richText,
                style: CustomStyle.yellowTextStyle.copyWith(
                    fontSize: Dimensions.headingTextSize4,
                    fontWeight: FontWeight.w500)),
            value.isNotEmpty
                ? GestureDetector(
                    onTap: onTap,
                    child: textWrap
                        ? Expanded(
                            child: SizedBox(
                              width: MediaQuery.sizeOf(context).width * 0.6,
                              child: CustomTitleHeadingWidget(
                                text: value,
                                textAlign: TextAlign.right,
                                style: CustomStyle.whiteTextStyle.copyWith(
                                    color: valueColor ??
                                        CustomColor.whiteColor.withOpacity(.6),
                                    fontSize: Dimensions.headingTextSize3,
                                    fontWeight: FontWeight.w600),
                                textOverflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                        : CustomTitleHeadingWidget(
                            text: value,
                            textAlign: TextAlign.right,
                            style: CustomStyle.whiteTextStyle.copyWith(
                                color: valueColor ??
                                    CustomColor.whiteColor.withOpacity(.6),
                                fontSize: Dimensions.headingTextSize3,
                                fontWeight: FontWeight.w600)),
                  )
                : Container(
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.widthSize * .5,
                      vertical: Dimensions.heightSize * .2,
                    ),
                    decoration: BoxDecoration(
                        color: color != null ? color!.withOpacity(.15) : null,
                        borderRadius:
                            BorderRadius.circular(Dimensions.radius * .4)),
                    child: CustomTitleHeadingWidget(
                        text: statusText,
                        style:
                            CustomStyle.statusTextStyle.copyWith(color: color)),
                  ),
          ],
        ),
      ],
    );
  }
}
