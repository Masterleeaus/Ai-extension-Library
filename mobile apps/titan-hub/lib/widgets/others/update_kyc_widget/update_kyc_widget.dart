import 'dart:io';

import 'package:dotted_border/dotted_border.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';

import '../../../controller/profile/update_kyc_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../views/others/custom_image_widget.dart';

File? imageFile;

class UpdateKycImageWidget extends StatefulWidget {
  const UpdateKycImageWidget(
      {super.key, required this.labelName, required this.fieldName});

  final String labelName;
  final String fieldName;

  @override
  State<UpdateKycImageWidget> createState() => _DropFileState();
}

class _DropFileState extends State<UpdateKycImageWidget> {
  final controller = Get.put(UpdateKycController());

  Future pickImage(imageSource) async {
    try {
      final image =
          await ImagePicker().pickImage(source: imageSource, imageQuality: 50);
      if (image == null) return;

      imageFile = File(image.path);

      if (controller.listFieldName.isNotEmpty) {
        if (controller.listFieldName.contains(widget.fieldName)) {
          int itemIndex = controller.listFieldName.indexOf(widget.fieldName);
          controller.listFieldName[itemIndex] = widget.fieldName;
          controller.listImagePath[itemIndex] = imageFile!.path;
        } else {
          controller.listImagePath.add(imageFile!.path);
          controller.listFieldName.add(widget.fieldName);
        }
      } else {
        controller.listImagePath.add(imageFile!.path);
        controller.listFieldName.add(widget.fieldName);
      }
      setState(() {
        controller.updateImageData(widget.fieldName, imageFile!.path);
      });
      Get.back();
      // CustomSnackBar.success('$labelName Added');
    } on PlatformException catch (_) {
      // CustomSnackBar.error('Error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        showModalBottomSheet(
          context: context,
          builder: (BuildContext context) {
            return _imagePickerBottomSheetWidget(context);
          },
        );
      },
      child: Container(
        color: imageFile == null
            ? Colors.transparent
            : CustomColor.primaryLightScaffoldBackgroundColor,
        child: DottedBorder(
          dashPattern: const [4, 2],
          strokeWidth: 2,
          color: CustomColor.primaryLightColor.withOpacity(0.2),
          child: Container(
            height: Dimensions.heightSize * 7,
            alignment: Alignment.center,
            margin: EdgeInsets.symmetric(
              horizontal: Dimensions.marginSizeHorizontal * 0.2,
              vertical: Dimensions.marginSizeVertical * 0.2,
            ),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.radius),
              image: DecorationImage(
                fit: BoxFit.cover,
                image: controller.getImagePath(widget.fieldName) == null
                    ? AssetImage(Assets.card.idcardBack.path) as ImageProvider
                    : FileImage(
                        File(
                          controller.getImagePath(widget.fieldName) ?? '',
                        ),
                      ),
              ),
            ),
            child: CustomImageWidget(
              path: Assets.icon.camera,
              height: Dimensions.heightSize * 1.4,
            ),
          ),
        ),
      ),
    );
  }

  _imagePickerBottomSheetWidget(BuildContext context) {
    return Container(
      width: double.infinity,
      height: MediaQuery.of(context).size.height * 0.15,
      margin: EdgeInsets.all(Dimensions.marginSizeVertical * 0.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: IconButton(
                onPressed: () {
                  pickImage(ImageSource.gallery);
                },
                icon: Icon(
                  Icons.image,
                  color: CustomColor.primaryLightColor,
                  size: 50,
                )),
          ),
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: IconButton(
                onPressed: () {
                  pickImage(ImageSource.camera);
                },
                icon: Icon(
                  Icons.camera,
                  color: CustomColor.primaryLightColor,
                  size: 50,
                )),
          ),
        ],
      ),
    );
  }
}
