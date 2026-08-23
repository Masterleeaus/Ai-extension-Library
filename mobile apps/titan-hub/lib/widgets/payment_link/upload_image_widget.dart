import 'dart:io';

import 'package:dotted_border/dotted_border.dart';

import '../../utils/basic_screen_imports.dart';

class UploadImageWidget extends StatefulWidget {
  final VoidCallback? onImagePick;
  final bool isImagePathSet;
  final bool isPicker;
  final String? imagePath;
  final String defaultImage;
  final String networkImage;
  final String title;
  final String partName;
  final bool isVisible;

  const UploadImageWidget({
    super.key,
    this.onImagePick,
    this.isImagePathSet = false,
    this.imagePath,
    this.isPicker = true,
    required this.title,
    required this.partName,
    required this.isVisible,
    required this.defaultImage,
    required this.networkImage,
  });

  @override
  State<UploadImageWidget> createState() => _UploadImageWidgetState();
}

class _UploadImageWidgetState extends State<UploadImageWidget> {
  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.transparent,
        borderRadius: BorderRadius.all(
          Radius.circular(Dimensions.radius),
        ),
      ),
      padding: EdgeInsets.only(
        left: Dimensions.paddingHorizontalSize * 0.15,
        right: Dimensions.paddingHorizontalSize * 0.15,
        bottom: Dimensions.paddingVerticalSize * 0.542,
      ),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Row(
            children: [
              TitleHeading4Widget(
                text: widget.partName,
                fontWeight: FontWeight.w600,
                fontSize: Dimensions.headingTextSize3,
              ),
              horizontalSpace(Dimensions.widthSize * 0.2),
              Visibility(
                visible: widget.isVisible,
                child: TitleHeading4Widget(
                  text: "(${Strings.optional.tr})",
                  fontWeight: FontWeight.w600,
                  fontSize: Dimensions.headingTextSize4,
                  color: CustomColor.primaryLightColor.withOpacity(.8),
                ),
              ),
            ],
          ),
          verticalSpace(Dimensions.heightSize * 0.75),
          DottedBorder(
            borderType: BorderType.RRect,
            dashPattern: const [3, 3],
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.paddingHorizontalSize * 0.3,
              vertical: Dimensions.paddingHorizontalSize * .25,
            ),
            radius: Radius.circular(Dimensions.radius * 0.8),
            color: CustomColor.primaryLightTextColor.withOpacity(0.15),
            strokeWidth: 2,
            child: Container(
              // alignment: Alignment.bottomCenter,
              padding: !widget.isPicker
                  ? EdgeInsets.only(
                      bottom: 10.h,
                    )
                  : EdgeInsets.zero,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
                image: widget.isImagePathSet
                    ? DecorationImage(
                        image: FileImage(File(widget.imagePath!)),
                        fit: BoxFit.cover,
                      )
                    : DecorationImage(
                        image: NetworkImage(widget.networkImage.isNotEmpty
                            ? widget.networkImage
                            : widget.defaultImage),
                        fit: BoxFit.cover,
                      ),
              ),
              height: MediaQuery.sizeOf(context).height * 0.10,

              child: Visibility(
                visible: widget.isPicker,
                child: Center(
                  child: InkWell(
                    onTap: widget.onImagePick,
                    child: const Icon(Icons.cloud_upload_outlined),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
